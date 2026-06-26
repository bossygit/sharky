<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->timestamps();
        });

        Schema::create('customer_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('order_number')->unique();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('customer_email');
            $table->string('customer_name');
            $table->string('customer_phone', 32);
            $table->text('delivery_address_text');
            $table->string('delivery_city')->default('Brazzaville');

            $table->string('status')->default('pending');
            $table->string('payment_method');
            $table->string('payment_status')->default('pending');

            $table->bigInteger('subtotal_minor');
            $table->bigInteger('shipping_minor');
            $table->bigInteger('total_minor');
            $table->char('currency_code', 3)->default('XAF');

            $table->text('customer_notes')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'placed_at']);
            $table->index('payment_status');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('variants')->nullOnDelete();
            $table->string('sku');
            $table->string('title');
            $table->unsignedInteger('quantity');
            $table->bigInteger('unit_price_minor');
            $table->bigInteger('line_total_minor');
            $table->timestamps();
        });

        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('provider_reference')->nullable();
            $table->bigInteger('amount_minor');
            $table->string('status', 32)->default('pending');
            $table->string('idempotency_key')->unique();
            $table->json('payload')->nullable();
            $table->timestamp('webhook_received_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customer_otps');
        Schema::dropIfExists('commerce_settings');
    }
};
