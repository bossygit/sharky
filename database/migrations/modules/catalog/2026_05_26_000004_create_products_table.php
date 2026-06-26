<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->integer('legacy_drupal_id')->nullable()->unique();
            $table->string('origin')->default('backoffice'); // drupal_migration | backoffice | api_import

            $table->string('canonical_path')->unique(); // ex: /25/acm-... ou /produits/{slug}
            $table->string('slug');

            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('title');
            $table->text('short_description_html')->nullable();
            $table->text('description_html')->nullable();

            $table->json('features')->nullable();
            $table->json('specifications')->nullable();
            $table->json('faqs')->nullable();

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->string('status')->default('draft'); // draft | published | archived
            $table->timestamp('published_at')->nullable();

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('search_index_dirty')->default(true);
            $table->string('ai_enrichment_status')->default('pending');
            $table->decimal('ai_confidence', 3, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index('slug');
        });

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
