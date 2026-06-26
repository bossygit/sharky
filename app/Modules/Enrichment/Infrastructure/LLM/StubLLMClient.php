<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Infrastructure\LLM;

use App\Modules\Enrichment\Domain\Contracts\LLMClient;
use App\Modules\Enrichment\Domain\Contracts\LLMRequest;
use App\Modules\Enrichment\Domain\Contracts\LLMResponse;

/**
 * Client local pour dev/tests sans clé API — génère un JSON structuré minimal.
 */
class StubLLMClient implements LLMClient
{
    public function complete(LLMRequest $request): LLMResponse
    {
        $userMessage = collect($request->messages)->reverse()->first(fn ($m) => ($m['role'] ?? '') === 'user');
        $user = is_array($userMessage) ? ($userMessage['content'] ?? '') : '';
        preg_match('/PRODUIT\s*:\s*(.+)/i', $user, $titleMatch);
        preg_match('/MARQUE\s*:\s*(.+)/i', $user, $brandMatch);

        $title = trim($titleMatch[1] ?? 'Produit');
        $brand = trim($brandMatch[1] ?? 'Marque');

        $payload = [
            'description_html' => '<p>'.e($title).' est un produit de la marque '.e($brand).', disponible sur nasande.cg.</p>',
            'features' => [
                'Qualité '.$brand,
                'Adapté au climat tropical',
                'Livraison à Brazzaville',
            ],
            'faqs' => [
                ['q' => 'Comment commander ?', 'a' => 'Ajoutez au panier et validez le checkout par email OTP.'],
                ['q' => 'Livraison où ?', 'a' => 'Brazzaville uniquement en V1.'],
            ],
            'meta_title' => mb_substr($title.' | '.$brand.' | nasande', 0, 60),
            'meta_description' => mb_substr('Découvrez '.$title.' ('.$brand.') sur nasande.cg — parapharmacie et beauté au Congo.', 0, 160),
            'evidence' => [
                ['field' => 'description_html', 'source_index' => 0, 'confidence' => 0.72],
            ],
        ];

        return new LLMResponse(content: json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
