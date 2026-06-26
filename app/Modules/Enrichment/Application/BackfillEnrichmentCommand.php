<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Application;

use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Console\Command;

class BackfillEnrichmentCommand extends Command
{
    protected $signature = 'enrichment:backfill {--limit=100 : Nombre max de produits à enfiler}';

    protected $description = 'Enqueue l\'enrichissement IA pour les produits sans description ou sans image';

    public function handle(EnrichmentOrchestrator $orchestrator): int
    {
        $limit = (int) $this->option('limit');

        $products = Product::query()
            ->where(function ($q) {
                $q->whereNull('description_html')
                    ->orWhere('description_html', '')
                    ->orWhereDoesntHave('images');
            })
            ->whereNotIn('ai_enrichment_status', ['review'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $enqueued = 0;
        foreach ($products as $product) {
            try {
                $orchestrator->enqueue($product);
                $enqueued++;
            } catch (\Throwable $e) {
                $this->warn("Produit #{$product->id} ignoré : {$e->getMessage()}");
            }
        }

        $this->info("{$enqueued} produit(s) enfilé(s) pour enrichissement.");

        return self::SUCCESS;
    }
}
