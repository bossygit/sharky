<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Domain\Services;

class SourceConfidenceScorer
{
    /** @param  list<array{url: string, title: string, excerpt: string}>  $sources */
    public function scoreField(string $field, mixed $value, array $sources, array $evidence): float
    {
        if ($value === null || $value === '' || $value === []) {
            return 0.0;
        }

        $score = 0.45;

        $fieldEvidence = array_values(array_filter(
            $evidence,
            fn ($row) => is_array($row) && ($row['field'] ?? null) === $field,
        ));

        if ($fieldEvidence !== []) {
            $score += 0.2;
        } else {
            $score -= 0.25;
        }

        if (count($sources) >= 2) {
            $score += 0.15;
        }

        foreach ($sources as $source) {
            $host = parse_url($source['url'] ?? '', PHP_URL_HOST) ?: '';
            if (preg_match('/(official|brand|marque|\.com)$/i', $host)) {
                $score += 0.1;
                break;
            }
        }

        if (is_string($value) && preg_match('/\d+\s*(kg|g|ml|cm|mm)/i', $value)) {
            $score -= 0.35;
        }

        return max(0.0, min(1.0, round($score, 2)));
    }

    public function average(array $scores): float
    {
        if ($scores === []) {
            return 0.0;
        }

        return round(array_sum($scores) / count($scores), 2);
    }
}
