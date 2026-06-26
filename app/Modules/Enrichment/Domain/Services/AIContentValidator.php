<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Domain\Services;

class AIContentValidator
{
    /** @return list<string> */
    public function validate(array $payload): array
    {
        $issues = [];

        if (isset($payload['meta_title']) && is_string($payload['meta_title']) && mb_strlen($payload['meta_title']) > 60) {
            $issues[] = 'meta_title dépasse 60 caractères.';
        }

        if (isset($payload['meta_description']) && is_string($payload['meta_description'])) {
            $len = mb_strlen($payload['meta_description']);
            if ($len < 80 || $len > 160) {
                $issues[] = 'meta_description hors plage recommandée (80-160).';
            }
        }

        if (isset($payload['features']) && is_array($payload['features'])) {
            $count = count($payload['features']);
            if ($count < 3 || $count > 7) {
                $issues[] = 'features doit contenir 3 à 7 éléments.';
            }
        }

        foreach (['probablement', 'généralement', 'peut-être'] as $marker) {
            $blob = json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '';
            if (stripos($blob, $marker) !== false) {
                $issues[] = 'Marqueur de flou détecté : '.$marker;
            }
        }

        if (isset($payload['description_html']) && is_string($payload['description_html'])) {
            if (preg_match('/\[[^\]]+\]\(https?:\/\//', $payload['description_html'])) {
                $issues[] = 'Liens markdown interdits dans description_html.';
            }
        }

        return $issues;
    }
}
