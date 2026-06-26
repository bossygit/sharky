<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Application;

use App\Modules\Catalog\Domain\Models\Variant;
use Illuminate\Contracts\Session\Session;
use InvalidArgumentException;

class CartService
{
    private const SESSION_KEY = 'commerce.cart';

    public function __construct(private Session $session) {}

    /** @return array<int, array{variant_id:int,product_id:int,quantity:int,title:string,sku:string,price_minor:int}> */
    public function items(): array
    {
        return $this->session->get(self::SESSION_KEY.'.items', []);
    }

    public function count(): int
    {
        return array_sum(array_column($this->items(), 'quantity'));
    }

    public function subtotalMinor(): int
    {
        return array_sum(array_map(
            fn (array $item) => $item['price_minor'] * $item['quantity'],
            $this->items(),
        ));
    }

    public function add(int $variantId, int $quantity = 1): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantité invalide.');
        }

        $variant = Variant::query()
            ->with('product')
            ->where('id', $variantId)
            ->where('status', 'active')
            ->firstOrFail();

        if ($variant->stock !== null && $variant->stock < $quantity) {
            throw new InvalidArgumentException('Stock insuffisant.');
        }

        $items = $this->items();
        $existingQty = $items[$variantId]['quantity'] ?? 0;
        $newQty = $existingQty + $quantity;

        if ($variant->stock !== null && $variant->stock < $newQty) {
            throw new InvalidArgumentException('Stock insuffisant.');
        }

        $items[$variantId] = [
            'variant_id' => $variant->id,
            'product_id' => $variant->product_id,
            'quantity' => $newQty,
            'title' => $variant->product->title,
            'sku' => $variant->sku,
            'price_minor' => $variant->price_minor,
        ];

        $this->session->put(self::SESSION_KEY, [
            'items' => $items,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function update(int $variantId, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($variantId);

            return;
        }

        $items = $this->items();
        if (! isset($items[$variantId])) {
            throw new InvalidArgumentException('Article introuvable dans le panier.');
        }

        $variant = Variant::findOrFail($variantId);
        if ($variant->stock !== null && $variant->stock < $quantity) {
            throw new InvalidArgumentException('Stock insuffisant.');
        }

        $items[$variantId]['quantity'] = $quantity;
        $this->session->put(self::SESSION_KEY, [
            'items' => $items,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function remove(int $variantId): void
    {
        $items = $this->items();
        unset($items[$variantId]);
        $this->session->put(self::SESSION_KEY, [
            'items' => $items,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function isEmpty(): bool
    {
        return $this->items() === [];
    }
}
