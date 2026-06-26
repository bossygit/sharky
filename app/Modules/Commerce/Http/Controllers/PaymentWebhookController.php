<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Domain\Models\Order;
use App\Modules\Commerce\Domain\Models\OrderPayment;
use App\Modules\Commerce\Infrastructure\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PaymentWebhookController extends Controller
{
    public function __construct(private PaymentManager $payments) {}

    public function airtel(Request $request): JsonResponse
    {
        return $this->handle($request, 'airtel_money');
    }

    public function mtn(Request $request): JsonResponse
    {
        return $this->handle($request, 'mtn_momo');
    }

    private function handle(Request $request, string $providerCode): JsonResponse
    {
        $provider = $this->payments->get($providerCode);
        $result = $provider->handleWebhook($request);

        if (! ($result['handled'] ?? false)) {
            return response()->json(['ok' => false, 'message' => $result['message'] ?? 'ignored'], 400);
        }

        $reference = $result['provider_reference'] ?? $request->input('reference');
        $payment = OrderPayment::query()
            ->where('provider', $providerCode)
            ->where('provider_reference', $reference)
            ->latest('id')
            ->first();

        if (! $payment) {
            return response()->json(['ok' => false, 'message' => 'payment not found'], 404);
        }

        if ($payment->webhook_received_at) {
            return response()->json(['ok' => true, 'message' => 'already processed']);
        }

        $payment->status = $result['status'] === 'paid' ? 'paid' : 'failed';
        $payment->webhook_received_at = now();
        $payment->payload = array_merge($payment->payload ?? [], ['webhook' => $request->all()]);
        $payment->save();

        if ($result['status'] === 'paid') {
            $payment->order->markPaid();
        }

        return response()->json(['ok' => true]);
    }
}
