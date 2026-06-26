<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Domain\Contracts;

final readonly class WebSearchResult
{
    public function __construct(
        public string $url,
        public string $title,
        public string $excerpt,
        public ?string $fetchedAt = null,
    ) {}

    /** @return array{url: string, title: string, excerpt: string, fetched_at: string|null} */
    public function toSourceArray(): array
    {
        return [
            'url' => $this->url,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'fetched_at' => $this->fetchedAt ?? now()->toIso8601String(),
        ];
    }
}

interface WebSearchClient
{
    /** @return list<WebSearchResult> */
    public function search(string $query, int $limit = 5): array;
}
