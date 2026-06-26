<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Domain\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    public function show(string $uuid): View
    {
        $order = Order::query()
            ->where('uuid', $uuid)
            ->with(['items', 'payments'])
            ->firstOrFail();

        $payment = $order->payments->first();
        $instructions = $payment?->payload['instructions'] ?? null;

        return view('commerce::orders.show', compact('order', 'instructions'));
    }
}
