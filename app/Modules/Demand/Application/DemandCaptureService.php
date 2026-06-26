<?php

declare(strict_types=1);

namespace App\Modules\Demand\Application;

use App\Modules\Demand\Domain\Models\DemandSignal;
use App\Modules\Search\Domain\Models\SearchLog;

class DemandCaptureService
{
    public function captureFromSearchLog(SearchLog $log): void
    {
        if ($log->hits_count !== 0) {
            return;
        }

        $keyword = $log->query_normalized;
        if ($keyword === '') {
            return;
        }

        $signal = DemandSignal::query()->firstOrNew(['keyword' => $keyword]);

        if (! $signal->exists) {
            $signal->first_seen_at = $log->occurred_at ?? now();
            $signal->example_queries = [$log->query];
        } else {
            $examples = $signal->example_queries ?? [];
            if (! in_array($log->query, $examples, true)) {
                array_unshift($examples, $log->query);
                $signal->example_queries = array_slice($examples, 0, 10);
            }
        }

        $signal->last_seen_at = $log->occurred_at ?? now();
        $signal->status = $signal->status ?: 'open';
        $signal->save();
    }
}
