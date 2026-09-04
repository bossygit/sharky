<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Application\CartService;
use App\Modules\Commerce\Application\CheckoutService;
use App\Modules\Commerce\Application\CustomerIdentityService;
use App\Modules\Commerce\Application\StorefrontContact;
use App\Modules\Commerce\Infrastructure\Payments\PaymentManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cart,
        private CheckoutService $checkout,
        private CustomerIdentityService $customers,
        private PaymentManager $payments,
    ) {}

    public function show(): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.show')->with('error', 'Votre panier est vide.');
        }

        return view('commerce::checkout.show', [
            'subtotal' => $this->cart->subtotalMinor(),
            'shipping' => $this->checkout->shippingFeeMinor(),
            'total' => $this->checkout->totalMinor(),
            'paymentMethods' => $this->payments->enabled(),
            'deliveryCity' => config('commerce.delivery.city', 'Brazzaville'),
            'contact' => StorefrontContact::current(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.show')->with('error', 'Panier vide.');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email'],
            'customer_phone' => ['required', 'string', 'max:32'],
            'delivery_address_text' => ['required', 'string', 'max:2000'],
            'payment_method' => ['required', 'in:cash_on_delivery,airtel_money,mtn_momo'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $customer = $this->customers->findOrCreate($data['customer_email'], $data['customer_name']);
            Auth::login($customer);

            $order = $this->checkout->placeOrder($customer, $data);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('order.show', $order->uuid)
            ->with('success', 'Commande enregistrée.');
    }
}
