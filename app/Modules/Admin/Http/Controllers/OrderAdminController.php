<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Commerce\Domain\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrderAdminController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::query()->with('items')->latest('placed_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(25)->withQueryString();

        return view('admin::orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'payments', 'user']);

        return view('admin::orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,awaiting_payment,paid,preparing,out_for_delivery,delivered,cancelled'],
            'payment_status' => ['nullable', 'in:pending,processing,paid,failed,refunded'],
        ]);

        $order->status = $data['status'];
        if (! empty($data['payment_status'])) {
            $order->payment_status = $data['payment_status'];
            if ($data['payment_status'] === 'paid' && ! $order->paid_at) {
                $order->paid_at = now();
            }
        }
        $order->save();

        return back()->with('success', 'Statut commande mis à jour.');
    }
}
