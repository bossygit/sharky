<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Infrastructure\WebSearch;

use App\Modules\Enrichment\Domain\Contracts\WebSearchClient;
use App\Modules\Enrichment\Domain\Contracts\WebSearchResult;

class StubWebSearchClient implements WebSearchClient
{
    public function search(string $query, int $limit = 5): array
    {
        $slug = rawurlencode($query);

        return array_slice([
            new WebSearchResult(
                url: 'https://example.com/products/'.$slug,
                title: 'Fiche produit — '.$query,
                excerpt: 'Informations officielles sur '.$query.'.',
            ),
            new WebSearchResult(
                url: 'https://www.pharmacie-exemple.cg/'.$slug,
                title: $query.' | Parapharmacie',
                excerpt: 'Description et usage de '.$query.' en pharmacie.',
            ),
        ], 0, $limit);
    }
}
