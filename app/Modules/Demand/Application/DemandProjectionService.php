<?php

declare(strict_types=1);

namespace App\Modules\Demand\Application;

use App\Modules\Demand\Domain\Models\DemandCluster;
use App\Modules\Demand\Domain\Models\DemandSignal;
use App\Modules\Search\Domain\Models\SearchLog;
use Illuminate\Support\Collection;

class DemandProjectionService
{
    private const JACCARD_THRESHOLD = 0.35;

    public function project(): array
    {
        $synced = $this->syncSignalsFromSearchLogs();
        $clustered = $this->reclusterOpenSignals();

        return [
            'signals_synced' => $synced,
            'clusters_updated' => $clustered,
        ];
    }

    private function syncSignalsFromSearchLogs(): int
    {
        $keywords = SearchLog::query()
            ->where('hits_count', 0)
            ->where('occurred_at', '>=', now()->subDays(30))
            ->distinct()
            ->pluck('query_normalized');

        $count = 0;

        foreach ($keywords as $keyword) {
            if ($keyword === '') {
                continue;
            }

            $this->upsertSignal($keyword);
            $count++;
        }

        return $count;
    }

    private function upsertSignal(string $keyword): DemandSignal
    {
        $base = SearchLog::query()
            ->where('hits_count', 0)
            ->where('query_normalized', $keyword);

        $frequency30d = (clone $base)
            ->where('occurred_at', '>=', now()->subDays(30))
            ->count();

        $frequency7d = (clone $base)
            ->where('occurred_at', '>=', now()->subDays(7))
            ->count();

        $uniqueSessions7d = (clone $base)
            ->where('occurred_at', '>=', now()->subDays(7))
            ->whereNotNull('session_id')
            ->distinct()
            ->count('session_id');

        $examples = SearchLog::query()
            ->where('hits_count', 0)
            ->where('query_normalized', $keyword)
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->pluck('query')
            ->unique()
            ->values()
            ->take(10)
            ->all();

        $firstSeen = (clone $base)->min('occurred_at');
        $lastSeen = (clone $base)->max('occurred_at');

        $urgency = $this->calculateUrgency($frequency7d, $uniqueSessions7d);

        return DemandSignal::updateOrCreate(
            ['keyword' => $keyword],
            [
                'frequency_7d' => $frequency7d,
                'frequency_30d' => $frequency30d,
                'unique_sessions_7d' => $uniqueSessions7d,
                'urgency' => $urgency,
                'example_queries' => $examples,
                'first_seen_at' => $firstSeen,
                'last_seen_at' => $lastSeen,
            ],
        );
    }

    private function reclusterOpenSignals(): int
    {
        /** @var Collection<int, DemandSignal> $signals */
        $signals = DemandSignal::query()
            ->where('status', 'open')
            ->orderByDesc('frequency_7d')
            ->get();

        DemandCluster::query()->update(['member_count' => 0]);
        DemandSignal::query()->where('status', 'open')->update(['cluster_id' => null]);

        /** @var array<int, array{id: int, ngrams: list<string>}> $activeClusters */
        $activeClusters = [];
        $touchedClusterIds = [];

        foreach ($signals as $signal) {
            $signalNgrams = TextSimilarity::ngrams($signal->keyword);
            $bestClusterId = null;
            $bestScore = 0.0;

            foreach ($activeClusters as $clusterMeta) {
                $score = TextSimilarity::jaccard($signalNgrams, $clusterMeta['ngrams']);
                if ($score >= self::JACCARD_THRESHOLD && $score > $bestScore) {
                    $bestScore = $score;
                    $bestClusterId = $clusterMeta['id'];
                }
            }

            if ($bestClusterId === null) {
                $cluster = DemandCluster::create([
                    'label' => $this->labelFromSignal($signal),
                    'member_count' => 0,
                    'ngram_signature' => $signalNgrams,
                ]);
                $bestClusterId = $cluster->id;
                $activeClusters[] = ['id' => $bestClusterId, 'ngrams' => $signalNgrams];
            }

            $signal->cluster_id = $bestClusterId;
            $signal->save();
            $touchedClusterIds[$bestClusterId] = true;
        }

        foreach (array_keys($touchedClusterIds) as $clusterId) {
            $members = DemandSignal::query()->where('cluster_id', $clusterId)->get();
            $labelSignal = $members->sortByDesc('frequency_7d')->first();

            DemandCluster::whereKey($clusterId)->update([
                'label' => $labelSignal ? $this->labelFromSignal($labelSignal) : 'Sans label',
                'member_count' => $members->count(),
                'ngram_signature' => TextSimilarity::ngrams($labelSignal?->keyword ?? ''),
            ]);
        }

        DemandCluster::query()
            ->where('member_count', 0)
            ->whereDoesntHave('signals')
            ->delete();

        return count($touchedClusterIds);
    }

    private function labelFromSignal(DemandSignal $signal): string
    {
        $examples = $signal->example_queries ?? [];

        return $examples[0] ?? $signal->keyword;
    }

    private function calculateUrgency(int $frequency7d, int $uniqueSessions7d): string
    {
        $level = match (true) {
            $frequency7d >= 50 => 'viral',
            $frequency7d >= 20 => 'high',
            $frequency7d >= 5 => 'medium',
            default => 'low',
        };

        if ($uniqueSessions7d > 5) {
            $level = match ($level) {
                'low' => 'medium',
                'medium' => 'high',
                'high' => 'viral',
                default => $level,
            };
        }

        return $level;
    }
}
