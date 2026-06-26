<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Infrastructure\Payments;

use App\Modules\Commerce\Contracts\PaymentProvider;
use App\Modules\Commerce\Domain\Models\Order;
use Illuminate\Http\Request;

/**
 * Intégration Airtel Money — V2 stub avec instructions USSD.
 * Brancher l'API officielle via AIRTEL_MONEY_* dans .env.
 */
class AirtelMoneyProvider implements PaymentProvider
{
    public function code(): string
    {
        return 'airtel_money';
    }

    public function label(): string
    {
        return (string) config('commerce.payments.airtel_money.label', 'Airtel Money');
    }

    public function isEnabled(): bool
    {
        return (bool) config('commerce.payments.airtel_money.enabled', true);
    }

    public function initiate(Order $order): array
    {
        $reference = 'AM-'.$order->order_number;
        $merchant = config('commerce.payments.airtel_money.merchant_code') ?: 'NASANDE';

        $order->payment_status = 'pending';
        $order->status = 'awaiting_payment';
        $order->save();

        $instructions = sprintf(
            'Composez *128# puis sélectionnez « Payer un marchand ». Code marchand : %s. Montant : %s FCFA. Référence : %s.',
            $merchant,
            number_format($order->total_minor, 0, ',', ' '),
            $reference,
        );

        return [
            'status' => 'pending',
            'provider_reference' => $reference,
            'instructions' => $instructions,
            'payload' => ['merchant_code' => $merchant],
        ];
    }

    public function handleWebhook(Request $request): array
    {
        $secret = config('commerce.payments.airtel_money.webhook_secret');
        if ($secret && $request->header('X-Airtel-Signature') !== hash_hmac('sha256', $request->getContent(), $secret)) {
            return ['handled' => false, 'status' => null, 'message' => 'Signature invalide'];
        }

        $reference = $request->input('reference') ?? $request->input('transaction_id');
        $status = $request->input('status');

        if (! $reference) {
            return ['handled' => false, 'status' => null, 'message' => 'Référence manquante'];
        }

        return [
            'handled' => true,
            'status' => in_array($status, ['SUCCESS', 'success', 'paid'], true) ? 'paid' : 'failed',
            'message' => null,
            'provider_reference' => $reference,
            'idempotency_key' => $request->header('X-Idempotency-Key') ?? $reference,
        ];
    }
}
