<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Infrastructure\Payments;

use App\Modules\Commerce\Contracts\PaymentProvider;
use App\Modules\Commerce\Domain\Models\Order;
use Illuminate\Http\Request;

class CashOnDeliveryProvider implements PaymentProvider
{
    public function code(): string
    {
        return 'cash_on_delivery';
    }

    public function label(): string
    {
        return (string) config('commerce.payments.cash_on_delivery.label', 'Paiement à la livraison');
    }

    public function isEnabled(): bool
    {
        return (bool) config('commerce.payments.cash_on_delivery.enabled', true);
    }

    public function initiate(Order $order): array
    {
        $order->payment_status = 'pending';
        $order->status = 'pending';
        $order->save();

        return [
            'status' => 'pending',
            'provider_reference' => 'COD-'.$order->order_number,
            'instructions' => 'Vous paierez en espèces ou Mobile Money à la livraison à Brazzaville.',
            'payload' => ['method' => 'cod'],
        ];
    }

    public function handleWebhook(Request $request): array
    {
        return ['handled' => false, 'status' => null, 'message' => 'COD ne supporte pas de webhook.'];
    }
}
