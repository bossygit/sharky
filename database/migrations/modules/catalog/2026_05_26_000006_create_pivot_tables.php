<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->primary(['product_id', 'category_id']);
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->smallInteger('position')->default(0);
            $table->string('role')->default('gallery'); // primary | gallery | lifestyle | spec
            $table->primary(['product_id', 'media_id']);
        });

        Schema::create('variant_media', function (Blueprint $table) {
            $table->foreignId('variant_id')->constrained('variants')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->smallInteger('position')->default(0);
            $table->primary(['variant_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_media');
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('product_categories');
    }
};
