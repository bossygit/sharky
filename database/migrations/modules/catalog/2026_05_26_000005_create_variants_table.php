<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('legacy_drupal_id')->nullable()->unique();

            $table->string('sku')->unique();
            $table->string('title')->nullable();

            $table->bigInteger('price_minor');
            $table->char('currency_code', 3)->default('XAF');
            $table->bigInteger('list_price_minor')->nullable();

            $table->integer('stock')->nullable();
            $table->integer('weight_grams')->nullable();

            $table->jsonb('options')->nullable(); // {color, size, volume}

            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active'); // active | inactive

            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
