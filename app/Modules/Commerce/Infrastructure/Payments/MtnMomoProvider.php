<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Infrastructure\Payments;

use App\Modules\Commerce\Contracts\PaymentProvider;
use App\Modules\Commerce\Domain\Models\Order;
use Illuminate\Http\Request;

/**
 * Intégration MTN MoMo — V2 stub avec instructions USSD.
 * Brancher l'API officielle via MTN_MOMO_* dans .env.
 */
class MtnMomoProvider implements PaymentProvider
{
    public function code(): string
    {
        return 'mtn_momo';
    }

    public function label(): string
    {
        return (string) config('commerce.payments.mtn_momo.label', 'MTN Mobile Money');
    }

    public function isEnabled(): bool
    {
        return (bool) config('commerce.payments.mtn_momo.enabled', true);
    }

    public function initiate(Order $order): array
    {
        $reference = 'MTN-'.$order->order_number;

        $order->payment_status = 'pending';
        $order->status = 'awaiting_payment';
        $order->save();

        $instructions = sprintf(
            'Composez *133# puis « Paiement marchand ». Montant : %s FCFA. Référence commande : %s.',
            number_format($order->total_minor, 0, ',', ' '),
            $reference,
        );

        return [
            'status' => 'pending',
            'provider_reference' => $reference,
            'instructions' => $instructions,
            'payload' => ['reference' => $reference],
        ];
    }

    public function handleWebhook(Request $request): array
    {
        $secret = (string) config('commerce.payments.mtn_momo.webhook_secret');
        if ($secret === '') {
            return ['handled' => false, 'status' => null, 'message' => 'Secret webhook manquant'];
        }
        if ($request->header('X-Mtn-Signature') !== hash_hmac('sha256', $request->getContent(), $secret)) {
            return ['handled' => false, 'status' => null, 'message' => 'Signature invalide'];
        }

        $reference = $request->input('externalId') ?? $request->input('reference');
        $status = $request->input('status');

        if (! $reference) {
            return ['handled' => false, 'status' => null, 'message' => 'Référence manquante'];
        }

        return [
            'handled' => true,
            'status' => in_array($status, ['SUCCESSFUL', 'successful', 'paid'], true) ? 'paid' : 'failed',
            'message' => null,
            'provider_reference' => $reference,
            'idempotency_key' => $request->header('X-Idempotency-Key') ?? $reference,
        ];
    }
}
