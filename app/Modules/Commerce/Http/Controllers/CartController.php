<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Application\CartService;
use App\Modules\Commerce\Application\CheckoutService;
use App\Modules\Catalog\Domain\Models\Variant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CartController extends Controller
{
    public function __construct(
        private CartService $cart,
        private CheckoutService $checkout,
    ) {}

    public function show(): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return view('commerce::cart.show', [
                'items' => [],
                'subtotal' => 0,
                'shipping' => $this->checkout->shippingFeeMinor(),
                'total' => $this->checkout->shippingFeeMinor(),
                'count' => 0,
            ]);
        }

        $variantIds = array_keys($this->cart->items());
        $variants = Variant::with('product')->whereIn('id', $variantIds)->get()->keyBy('id');
        $lines = [];
        foreach ($this->cart->items() as $vid => $item) {
            $lines[] = ['item' => $item, 'variant' => $variants->get($vid)];
        }

        return view('commerce::cart.show', [
            'lines' => $lines,
            'subtotal' => $this->cart->subtotalMinor(),
            'shipping' => $this->checkout->shippingFeeMinor(),
            'total' => $this->checkout->totalMinor(),
            'count' => $this->cart->count(),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        try {
            $this->cart->add((int) $data['variant_id'], (int) ($data['quantity'] ?? 1));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('cart.show')->with('success', 'Produit ajouté au panier.');
    }

    public function update(Request $request, int $variantId): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:99']]);

        try {
            $this->cart->update($variantId, (int) $data['quantity']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Panier mis à jour.');
    }

    public function remove(int $variantId): RedirectResponse
    {
        $this->cart->remove($variantId);

        return back()->with('success', 'Article retiré.');
    }
}
