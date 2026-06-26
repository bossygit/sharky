<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Application;

use App\Models\User;
use App\Modules\Commerce\Domain\Models\CommerceSetting;
use App\Modules\Commerce\Domain\Models\Order;
use App\Modules\Commerce\Domain\Models\OrderItem;
use App\Modules\Commerce\Domain\Models\OrderPayment;
use App\Modules\Commerce\Infrastructure\Payments\PaymentManager;
use App\Modules\Commerce\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(
        private CartService $cart,
        private PaymentManager $payments,
    ) {}

    public function shippingFeeMinor(): int
    {
        return CommerceSetting::shippingFeeMinor();
    }

    public function totalMinor(): int
    {
        return $this->cart->subtotalMinor() + $this->shippingFeeMinor();
    }

    /**
     * @param  array{customer_name:string,customer_email:string,customer_phone:string,delivery_address_text:string,payment_method:string,customer_notes?:?string}  $data
     */
    public function placeOrder(User $customer, array $data): Order
    {
        if ($this->cart->isEmpty()) {
            throw new InvalidArgumentException('Panier vide.');
        }

        $city = config('commerce.delivery.city', 'Brazzaville');
        $provider = $this->payments->get($data['payment_method']);

        if (! $provider->isEnabled()) {
            throw new InvalidArgumentException('Mode de paiement indisponible.');
        }

        return DB::transaction(function () use ($customer, $data, $city, $provider) {
            $subtotal = $this->cart->subtotalMinor();
            $shipping = $this->shippingFeeMinor();
            $total = $subtotal + $shipping;

            $order = Order::create([
                'user_id' => $customer->id,
                'customer_email' => $data['customer_email'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'delivery_address_text' => $data['delivery_address_text'],
                'delivery_city' => $city,
                'payment_method' => $provider->code(),
                'subtotal_minor' => $subtotal,
                'shipping_minor' => $shipping,
                'total_minor' => $total,
                'currency_code' => config('commerce.currency', 'XAF'),
                'customer_notes' => $data['customer_notes'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($this->cart->items() as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'sku' => $item['sku'],
                    'title' => $item['title'],
                    'quantity' => $item['quantity'],
                    'unit_price_minor' => $item['price_minor'],
                    'line_total_minor' => $item['price_minor'] * $item['quantity'],
                ]);
            }

            $init = $provider->initiate($order);

            OrderPayment::create([
                'order_id' => $order->id,
                'provider' => $provider->code(),
                'provider_reference' => $init['provider_reference'],
                'amount_minor' => $total,
                'status' => $init['status'],
                'idempotency_key' => (string) Str::uuid(),
                'payload' => array_merge($init['payload'] ?? [], [
                    'instructions' => $init['instructions'] ?? null,
                ]),
            ]);

            $this->cart->clear();

            Mail::to($order->customer_email)->send(new OrderConfirmationMail($order->fresh(['items'])));

            return $order->fresh(['items', 'payments']);
        });
    }
}
