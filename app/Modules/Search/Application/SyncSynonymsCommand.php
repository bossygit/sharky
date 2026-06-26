<?php

declare(strict_types=1);

namespace App\Modules\Search\Application;

use App\Modules\Search\Domain\Models\SearchSynonym;
use Illuminate\Console\Command;
use Meilisearch\Client;

class SyncSynonymsCommand extends Command
{
    protected $signature = 'search:sync-synonyms';

    protected $description = 'Pousse les synonymes admin vers Meilisearch';

    public function handle(): int
    {
        if (config('scout.driver') !== 'meilisearch') {
            $this->warn('SCOUT_DRIVER != meilisearch, skip.');

            return self::SUCCESS;
        }

        $host = config('scout.meilisearch.host');
        if (! $host) {
            return self::SUCCESS;
        }

        $synonyms = [];
        foreach (SearchSynonym::all() as $row) {
            $synonyms[$row->term] = $row->synonyms;
        }

        try {
            $client = new Client($host, config('scout.meilisearch.key') ?: null);
            $client->index('products')->updateSynonyms($synonyms);
            $this->info(count($synonyms).' groupe(s) de synonymes synchronisés.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
