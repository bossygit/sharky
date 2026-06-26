<?php

declare(strict_types=1);

namespace App\Modules\Demand\Application;

final class TextSimilarity
{
    /** @return list<string> */
    public static function ngrams(string $text, int $n = 3): array
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? '';

        if ($text === '') {
            return [];
        }

        if (strlen($text) < $n) {
            return [$text];
        }

        $grams = [];
        for ($i = 0; $i <= strlen($text) - $n; $i++) {
            $grams[] = substr($text, $i, $n);
        }

        return array_values(array_unique($grams));
    }

    /** @param  list<string>  $a  @param  list<string>  $b */
    public static function jaccard(array $a, array $b): float
    {
        if ($a === [] && $b === []) {
            return 1.0;
        }

        $intersect = count(array_intersect($a, $b));
        $union = count(array_unique(array_merge($a, $b)));

        return $union === 0 ? 0.0 : $intersect / $union;
    }
}
