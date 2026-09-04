<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\Variant;
use App\Modules\Commerce\Application\CartService;
use App\Modules\Commerce\Domain\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommerceCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_checkout_with_cod_creates_order(): void
    {
        Mail::fake();

        $product = Product::create([
            'uuid' => (string) Str::uuid(),
            'origin' => 'backoffice',
            'canonical_path' => '/produits/test-checkout',
            'slug' => 'test-checkout',
            'title' => 'Produit test checkout',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $variant = Variant::create([
            'product_id' => $product->id,
            'sku' => 'CHK-001',
            'price_minor' => 5000,
            'currency_code' => 'XAF',
            'is_default' => true,
            'status' => 'active',
            'stock' => 10,
        ]);

        $this->post(route('cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.show'));

        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Jean Test',
            'customer_email' => 'client@test.cg',
            'customer_phone' => '+242061234567',
            'delivery_address_text' => 'Poto-Poto, Brazzaville',
            'payment_method' => 'cash_on_delivery',
        ]);

        $order = Order::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('order.show', $order->uuid));
        $this->assertSame('pending', $order->status);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(10000 + (int) config('commerce.delivery.fee_minor', 1500), $order->total_minor);

        $this->assertTrue(app(CartService::class)->isEmpty());
    }
}
