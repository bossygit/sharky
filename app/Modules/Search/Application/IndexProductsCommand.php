<?php

declare(strict_types=1);

namespace App\Modules\Search\Application;

use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Search\Domain\Models\SearchSynonym;
use Illuminate\Console\Command;
use Meilisearch\Client;

class IndexProductsCommand extends Command
{
    protected $signature = 'search:index-products {--fresh : Recréer l\'index Meilisearch}';

    protected $description = 'Indexe les produits publiés dans Meilisearch via Scout';

    public function handle(): int
    {
        if (config('scout.driver') !== 'meilisearch') {
            $this->warn('SCOUT_DRIVER n\'est pas meilisearch — indexation Scout quand même.');
        }

        if ($this->option('fresh')) {
            $this->call('scout:flush', ['model' => Product::class]);
        }

        $this->info('Import Scout…');
        $this->call('scout:import', ['model' => Product::class]);

        if (config('scout.driver') === 'meilisearch') {
            $this->configureMeilisearchIndex();
            $this->call('search:sync-synonyms');
        }

        Product::query()->where('status', 'published')->update(['search_index_dirty' => false]);

        $this->info('Indexation terminée.');

        return self::SUCCESS;
    }

    private function configureMeilisearchIndex(): void
    {
        $host = config('scout.meilisearch.host');
        $key = config('scout.meilisearch.key');

        if (! $host) {
            return;
        }

        try {
            $client = new Client($host, $key ?: null);
            $index = $client->index('products');
            $index->updateSettings([
                'searchableAttributes' => [
                    'title',
                    'brand_name',
                    'category_names',
                    'description_text',
                    'sku',
                ],
                'filterableAttributes' => ['status', 'price_minor'],
                'sortableAttributes' => ['price_minor', 'title'],
                'typoTolerance' => ['enabled' => true],
            ]);
            $this->info('Paramètres Meilisearch mis à jour.');
        } catch (\Throwable $e) {
            $this->warn('Meilisearch settings : '.$e->getMessage());
        }
    }
}
