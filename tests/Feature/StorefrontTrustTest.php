<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\Variant;
use App\Modules\Commerce\Application\CartService;
use App\Modules\Commerce\Domain\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontTrustTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_storefront_contact_and_home_shows_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.commerce.settings.update'), [
                'shipping_fee_minor' => 1500,
                'contact_phone' => '+242 06 123 45 67',
                'contact_whatsapp' => '+242061234567',
                'contact_email' => 'bonjour@nasande.cg',
            ])
            ->assertRedirect();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('tel:+242061234567', false)
            ->assertSee('https://wa.me/242061234567', false)
            ->assertSee(route('pages.contact', absolute: false), false)
            ->assertSee(route('pages.terms', absolute: false), false)
            ->assertSee(route('pages.returns', absolute: false), false)
            ->assertSee(route('pages.about', absolute: false), false);
    }

    public function test_empty_contact_settings_hide_phone_and_whatsapp_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('https://wa.me/', false)
            ->assertDontSee('tel:+', false);
    }

    public function test_trust_pages_are_reachable(): void
    {
        foreach ([
            'pages.contact' => 'Contact',
            'pages.about' => 'propos',
            'pages.terms' => 'Conditions',
            'pages.returns' => 'Retours',
        ] as $route => $needle) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee($needle);
        }
    }

    public function test_sitemap_includes_trust_pages(): void
    {
        $xml = $this->get(route('sitemap'))->assertOk()->getContent();

        $this->assertStringContainsString(url('/contact'), $xml);
        $this->assertStringContainsString(url('/a-propos'), $xml);
        $this->assertStringContainsString(url('/cgv'), $xml);
        $this->assertStringContainsString(url('/retours'), $xml);
    }

    public function test_product_page_includes_whatsapp_link_when_configured(): void
    {
        $this->saveContact();

        $product = Product::create([
            'uuid' => (string) Str::uuid(),
            'origin' => 'backoffice',
            'canonical_path' => '/produits/creme-test-wa',
            'slug' => 'creme-test-wa',
            'title' => 'Crème test WhatsApp',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Variant::create([
            'product_id' => $product->id,
            'sku' => 'WA-001',
            'price_minor' => 2500,
            'currency_code' => 'XAF',
            'is_default' => true,
            'status' => 'active',
            'stock' => 4,
        ]);

        $this->get('/produits/creme-test-wa')
            ->assertOk()
            ->assertSee('https://wa.me/242061234567', false)
            ->assertSee(rawurlencode('Crème test WhatsApp'), false);
    }

    public function test_checkout_creates_order_without_otp(): void
    {
        Mail::fake();

        $product = Product::create([
            'uuid' => (string) Str::uuid(),
            'origin' => 'backoffice',
            'canonical_path' => '/produits/test-checkout-no-otp',
            'slug' => 'test-checkout-no-otp',
            'title' => 'Produit checkout sans OTP',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $variant = Variant::create([
            'product_id' => $product->id,
            'sku' => 'CHK-OTP-0',
            'price_minor' => 5000,
            'currency_code' => 'XAF',
            'is_default' => true,
            'status' => 'active',
            'stock' => 10,
        ]);

        $this->post(route('cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.show'));

        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Marie Test',
            'customer_email' => 'marie@test.cg',
            'customer_phone' => '+242061111111',
            'delivery_address_text' => 'Bacongo, Brazzaville',
            'payment_method' => 'cash_on_delivery',
        ]);

        $order = Order::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('order.show', $order->uuid));
        $this->assertSame('marie@test.cg', $order->customer_email);
        $this->assertDatabaseHas('users', [
            'email' => 'marie@test.cg',
            'role' => 'customer',
        ]);
        $this->assertTrue(app(CartService::class)->isEmpty());
    }

    public function test_checkout_otp_route_is_removed(): void
    {
        $this->post('/checkout/otp', ['email' => 'client@test.cg'])
            ->assertNotFound();
    }

    public function test_checkout_page_has_no_otp_fields(): void
    {
        $this->saveContact();

        $product = Product::create([
            'uuid' => (string) Str::uuid(),
            'origin' => 'backoffice',
            'canonical_path' => '/produits/test-checkout-ui',
            'slug' => 'test-checkout-ui',
            'title' => 'Produit checkout UI',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $variant = Variant::create([
            'product_id' => $product->id,
            'sku' => 'CHK-UI',
            'price_minor' => 3000,
            'currency_code' => 'XAF',
            'is_default' => true,
            'status' => 'active',
            'stock' => 3,
        ]);

        $this->post(route('cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertDontSee('otp_code', false)
            ->assertDontSee('Envoyer le code')
            ->assertSee('https://wa.me/242061234567', false);
    }

    private function saveContact(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.commerce.settings.update'), [
                'shipping_fee_minor' => 1500,
                'contact_phone' => '+242 06 123 45 67',
                'contact_whatsapp' => '242061234567',
                'contact_email' => 'bonjour@nasande.cg',
            ]);

        $this->app['auth']->forgetGuards();
    }
}
