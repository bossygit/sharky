<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Application;

use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Enrichment\Domain\Models\EnrichmentDraft;
use App\Modules\Enrichment\Domain\Models\EnrichmentJob;
use App\Modules\Observability\Application\DomainEventRecorder;
use Illuminate\Support\Facades\DB;

class ApplyEnrichmentService
{
    public function __construct(private DomainEventRecorder $events) {}

    public function approveAll(Product $product, int $reviewerId): void
    {
        $drafts = EnrichmentDraft::query()
            ->where('product_id', $product->id)
            ->whereIn('decision', ['pending', 'edited'])
            ->get();

        if ($drafts->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($product, $drafts, $reviewerId) {
            $updates = [];
            foreach ($drafts as $draft) {
                $updates[$draft->field] = $draft->proposedScalar();
                $draft->update([
                    'decision' => 'approved',
                    'reviewer_id' => $reviewerId,
                    'reviewed_at' => now(),
                ]);
            }

            $product->update(array_merge($updates, [
                'ai_enrichment_status' => 'approved',
                'search_index_dirty' => true,
            ]));

            EnrichmentJob::query()
                ->where('product_id', $product->id)
                ->where('status', 'draft')
                ->update(['status' => 'approved']);
        });

        $this->events->record(
            aggregateType: 'product',
            aggregateId: (string) $product->id,
            type: 'EnrichmentApproved',
            payload: ['reviewer_id' => $reviewerId],
        );
    }

    public function rejectAll(Product $product, int $reviewerId, ?string $notes = null): void
    {
        EnrichmentDraft::query()
            ->where('product_id', $product->id)
            ->where('decision', 'pending')
            ->update([
                'decision' => 'rejected',
                'reviewer_id' => $reviewerId,
                'reviewed_at' => now(),
            ]);

        $product->update(['ai_enrichment_status' => 'rejected']);

        EnrichmentJob::query()
            ->where('product_id', $product->id)
            ->where('status', 'draft')
            ->update([
                'status' => 'rejected',
                'error' => $notes,
            ]);
    }

    public function updateDraft(EnrichmentDraft $draft, mixed $value, int $reviewerId): void
    {
        $draft->update([
            'value_proposed' => ['value' => $value],
            'decision' => 'edited',
            'reviewer_id' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }
}
