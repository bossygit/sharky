<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Application;

use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Enrichment\Domain\Contracts\LLMClient;
use App\Modules\Enrichment\Domain\Contracts\LLMRequest;
use App\Modules\Enrichment\Domain\Models\EnrichmentDraft;
use App\Modules\Enrichment\Domain\Models\EnrichmentJob;
use App\Modules\Enrichment\Domain\Services\AIContentValidator;
use App\Modules\Enrichment\Domain\Services\EnrichmentPromptBuilder;
use App\Modules\Enrichment\Domain\Services\ProductResearchService;
use App\Modules\Enrichment\Domain\Services\SourceConfidenceScorer;
use App\Modules\Observability\Application\DomainEventRecorder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EnrichmentOrchestrator
{
    public function __construct(
        private ProductResearchService $research,
        private EnrichmentPromptBuilder $prompts,
        private LLMClient $llm,
        private AIContentValidator $validator,
        private SourceConfidenceScorer $scorer,
        private DomainEventRecorder $events,
    ) {}

    public function enqueue(Product $product): EnrichmentJob
    {
        if (EnrichmentJob::query()
            ->where('product_id', $product->id)
            ->whereIn('status', ['queued', 'running', 'draft'])
            ->exists()
        ) {
            throw new RuntimeException('Un enrichissement est déjà en cours pour ce produit.');
        }

        $job = EnrichmentJob::create([
            'product_id' => $product->id,
            'status' => 'queued',
        ]);

        EnrichProductJob::dispatch($job->id);

        $this->events->record(
            aggregateType: 'product',
            aggregateId: (string) $product->id,
            type: 'EnrichmentRequested',
            payload: ['job_id' => $job->id],
        );

        return $job;
    }

    public function process(int $jobId): void
    {
        $job = EnrichmentJob::query()->with('product.brand')->findOrFail($jobId);
        $product = $job->product;

        $job->update([
            'status' => 'running',
            'attempts' => $job->attempts + 1,
            'started_at' => now(),
            'error' => null,
        ]);

        try {
            $sources = $this->research->research($product);
            if ($sources === []) {
                throw new RuntimeException('Aucune source web trouvée.');
            }

            $response = $this->llm->complete(new LLMRequest(
                messages: [
                    ['role' => 'system', 'content' => $this->prompts->systemPrompt()],
                    ['role' => 'user', 'content' => $this->prompts->build($product, array_map(
                        fn (array $s) => new \App\Modules\Enrichment\Domain\Contracts\WebSearchResult(
                            $s['url'],
                            $s['title'],
                            $s['excerpt'],
                            $s['fetched_at'] ?? null,
                        ),
                        $sources,
                    ))],
                ],
            ));

            $payload = json_decode($response->content, true);
            if (! is_array($payload)) {
                throw new RuntimeException('JSON LLM invalide.');
            }

            $issues = $this->validator->validate($payload);
            $evidence = is_array($payload['evidence'] ?? null) ? $payload['evidence'] : [];

            DB::transaction(function () use ($job, $product, $payload, $sources, $evidence, $issues) {
                EnrichmentDraft::query()->where('job_id', $job->id)->delete();

                $fieldScores = [];
                foreach (config('enrichment.fields', []) as $field) {
                    if (! array_key_exists($field, $payload) || $payload[$field] === null) {
                        continue;
                    }

                    $confidence = $this->scorer->scoreField($field, $payload[$field], $sources, $evidence);
                    $fieldScores[] = $confidence;

                    EnrichmentDraft::create([
                        'job_id' => $job->id,
                        'product_id' => $product->id,
                        'field' => $field,
                        'value_proposed' => ['value' => $payload[$field]],
                        'value_current' => ['value' => $product->{$field}],
                        'confidence' => $confidence,
                        'sources' => $sources,
                        'decision' => 'pending',
                    ]);
                }

                if ($fieldScores === []) {
                    throw new RuntimeException('Le LLM n\'a proposé aucun champ enrichissable.');
                }

                $avgConfidence = $this->scorer->average($fieldScores);

                $job->update([
                    'status' => 'draft',
                    'completed_at' => now(),
                    'error' => $issues !== [] ? implode(' ', $issues) : null,
                ]);

                $product->update([
                    'ai_enrichment_status' => 'review',
                    'ai_confidence' => $avgConfidence,
                ]);
            });

            $this->events->record(
                aggregateType: 'enrichment_job',
                aggregateId: (string) $job->id,
                type: 'EnrichmentDraftReady',
                payload: ['product_id' => $product->id, 'drafts' => $job->drafts()->count()],
            );
        } catch (\Throwable $e) {
            $job->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
