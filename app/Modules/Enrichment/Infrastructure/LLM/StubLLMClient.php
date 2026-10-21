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
            'description_html' => '<p>'.e($title).' est un vin de la maison '.e($brand).', disponible chez Nasande Vins.</p>',
            'features' => [
                'Sélection caviste',
                'Livraison à Brazzaville',
                'Paiement à la livraison ou Mobile Money',
            ],
            'faqs' => [
                ['q' => 'Comment commander ?', 'a' => 'Ajoutez au panier et validez le checkout ; nous vous appelons pour confirmer.'],
                ['q' => 'Livraison où ?', 'a' => 'Brazzaville ; Pointe-Noire et autres villes sur demande.'],
            ],
            'meta_title' => mb_substr($title.' | '.$brand.' | Nasande Vins', 0, 60),
            'meta_description' => mb_substr('Découvrez '.$title.' ('.$brand.') chez Nasande Vins — vins et spiritueux livrés à Brazzaville.', 0, 160),
            'evidence' => [
                ['field' => 'description_html', 'source_index' => 0, 'confidence' => 0.72],
            ],
        ];

        return new LLMResponse(content: json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
