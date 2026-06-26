<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Domain\Services;

use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Enrichment\Domain\Contracts\WebSearchClient;

class ProductResearchService
{
    public function __construct(private WebSearchClient $search) {}

    /** @return list<array{url: string, title: string, excerpt: string, fetched_at: string|null}> */
    public function research(Product $product): array
    {
        $product->loadMissing('brand');
        $query = trim($product->title.' '.($product->brand?->name ?? ''));
        $limit = (int) config('enrichment.websearch.max_sources', 5);

        return array_map(
            fn ($result) => $result->toSourceArray(),
            $this->search->search($query, $limit),
        );
    }
}
