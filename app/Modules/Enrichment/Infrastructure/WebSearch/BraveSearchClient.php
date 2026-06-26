<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Infrastructure\WebSearch;

use App\Modules\Enrichment\Domain\Contracts\WebSearchClient;
use App\Modules\Enrichment\Domain\Contracts\WebSearchResult;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BraveSearchClient implements WebSearchClient
{
    public function search(string $query, int $limit = 5): array
    {
        $apiKey = config('enrichment.websearch.brave.api_key');
        if (! $apiKey) {
            throw new RuntimeException('BRAVE_SEARCH_API_KEY manquante.');
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-Subscription-Token' => $apiKey,
        ])
            ->timeout((int) config('enrichment.websearch.brave.timeout', 20))
            ->get(config('enrichment.websearch.brave.base_url'), [
                'q' => $query,
                'count' => min($limit, 10),
                'search_lang' => 'fr',
                'country' => 'CG',
            ])
            ->throw();

        $results = [];
        foreach ($response->json('web.results', []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $url = (string) ($row['url'] ?? '');
            if ($url === '') {
                continue;
            }
            $results[] = new WebSearchResult(
                url: $url,
                title: (string) ($row['title'] ?? $url),
                excerpt: (string) ($row['description'] ?? ''),
            );
            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }
}
