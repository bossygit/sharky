<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Commerce\Domain\Models\Order;
use App\Modules\Commerce\Domain\Models\OrderPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_webhook_is_excluded_from_csrf(): void
    {
        config(['commerce.payments.airtel_money.webhook_secret' => 'test-secret']);

        $payload = json_encode(['reference' => 'missing', 'status' => 'paid']);
        $signature = hash_hmac('sha256', $payload, 'test-secret');

        $this->call(
            'POST',
            '/webhooks/payments/airtel',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-Airtel-Signature' => $signature,
            ],
            $payload,
        )->assertStatus(404)->assertJson(['ok' => false]);
    }

    public function test_payment_webhook_rejected_without_shared_secret(): void
    {
        config(['commerce.payments.airtel_money.webhook_secret' => null]);

        $this->postJson('/webhooks/payments/airtel', [
            'reference' => 'AM-1',
            'status' => 'paid',
        ])->assertStatus(400)->assertJson(['ok' => false]);
    }

    public function test_signed_airtel_webhook_marks_order_paid(): void
    {
        config(['commerce.payments.airtel_money.webhook_secret' => 'test-secret']);

        $order = Order::create([
            'customer_email' => 'client@test.cg',
            'customer_name' => 'Jean',
            'customer_phone' => '+242061234567',
            'delivery_address_text' => 'Poto-Poto',
            'status' => 'awaiting_payment',
            'payment_method' => 'airtel_money',
            'payment_status' => 'pending',
            'subtotal_minor' => 5000,
            'shipping_minor' => 1500,
            'total_minor' => 6500,
        ]);

        OrderPayment::create([
            'order_id' => $order->id,
            'provider' => 'airtel_money',
            'provider_reference' => 'AM-'.$order->order_number,
            'amount_minor' => 6500,
            'status' => 'pending',
            'idempotency_key' => 'AM-'.$order->order_number,
        ]);

        $payload = json_encode([
            'reference' => 'AM-'.$order->order_number,
            'status' => 'SUCCESS',
        ]);
        $signature = hash_hmac('sha256', $payload, 'test-secret');

        $this->call(
            'POST',
            '/webhooks/payments/airtel',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-Airtel-Signature' => $signature,
            ],
            $payload,
        )->assertOk()->assertJson(['ok' => true]);

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_otp_flash_does_not_mention_mailpit(): void
    {
        Mail::fake();

        $this->from('/checkout')
            ->post(route('checkout.otp'), ['email' => 'client@test.cg'])
            ->assertRedirect();

        $message = session('success', '');
        $this->assertStringNotContainsStringIgnoringCase('mailpit', (string) $message);
        $this->assertStringContainsString('client@test.cg', (string) $message);
    }

    public function test_admin_login_is_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'admin@nasande.cg',
            'role' => 'admin',
            'password' => Hash::make('correct-horse'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->from(route('admin.login'))
                ->post(route('admin.login.submit'), [
                    'email' => 'admin@nasande.cg',
                    'password' => 'wrong-password',
                ]);
        }

        $this->from(route('admin.login'))
            ->post(route('admin.login.submit'), [
                'email' => 'admin@nasande.cg',
                'password' => 'wrong-password',
            ])
            ->assertStatus(429);
    }

    public function test_checkout_otp_is_rate_limited(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->from('/checkout')
                ->post(route('checkout.otp'), ['email' => 'client@test.cg']);
        }

        $this->from('/checkout')
            ->post(route('checkout.otp'), ['email' => 'client@test.cg'])
            ->assertStatus(429);
    }

    public function test_admin_create_user_requires_password_option(): void
    {
        $this->artisan('admin:create-user')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'admin@nasande.cg']);
    }

    public function test_admin_create_user_with_password_succeeds(): void
    {
        $this->artisan('admin:create-user', [
            '--email' => 'admin@nasande.cg',
            '--password' => 'UniqueAdminPass!42',
        ])->assertSuccessful();

        $user = User::where('email', 'admin@nasande.cg')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('UniqueAdminPass!42', $user->password));
    }
}
