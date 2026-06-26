<?php

declare(strict_types=1);

namespace App\Modules\Search\Application;

use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Observability\Application\DomainEventRecorder;
use App\Modules\Search\Domain\Models\SearchLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchService
{
    public function __construct(private DomainEventRecorder $events) {}

    public function normalizeQuery(string $query): string
    {
        $q = Str::lower(trim($query));
        $q = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $q) ?: $q;

        return preg_replace('/\s+/', ' ', $q) ?? $q;
    }

    /** @return Collection<int, Product> */
    public function search(string $query, int $limit = 24): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return Product::query()->whereRaw('1=0')->get();
        }

        if (config('scout.driver') === 'meilisearch') {
            try {
                return Product::search($query)
                    ->where('status', 'published')
                    ->take($limit)
                    ->get()
                    ->load(['brand', 'variants', 'images']);
            } catch (\Throwable) {
                // fallback DB ci-dessous
            }
        }

        $like = '%'.$query.'%';
        $op = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return Product::published()
            ->with(['brand', 'variants', 'images'])
            ->where(function ($q) use ($like, $op) {
                $q->where('title', $op, $like)
                    ->orWhere('meta_title', $op, $like)
                    ->orWhere('description_html', $op, $like);
            })
            ->limit($limit)
            ->get();
    }

    public function logSearch(
        string $query,
        int $hitsCount,
        ?string $sessionId = null,
        ?int $durationMs = null,
    ): SearchLog {
        $normalized = $this->normalizeQuery($query);

        $log = SearchLog::create([
            'session_id' => $sessionId,
            'query' => $query,
            'query_normalized' => $normalized,
            'hits_count' => $hitsCount,
            'duration_ms' => $durationMs,
            'occurred_at' => now(),
        ]);

        $this->events->record(
            aggregateType: 'search',
            aggregateId: (string) $log->id,
            type: $hitsCount === 0 ? 'SearchReturnedZeroResults' : 'SearchPerformed',
            payload: [
                'query' => $query,
                'query_normalized' => $normalized,
                'hits_count' => $hitsCount,
            ],
            metadata: ['session_id' => $sessionId],
        );

        if ($hitsCount === 0) {
            app(\App\Modules\Demand\Application\DemandCaptureService::class)->captureFromSearchLog($log);
        }

        return $log;
    }
}
