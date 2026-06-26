<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_hash')->nullable();
            $table->string('user_agent_hash')->nullable();
            $table->string('locale', 16)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('device', 32)->nullable();
            $table->json('flags')->nullable();
            $table->decimal('intent_score', 4, 3)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();
        });

        Schema::create('behavior_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->text('url')->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');

            $table->index(['session_id', 'occurred_at']);
            $table->index(['type', 'occurred_at']);
        });

        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->nullable();
            $table->string('query');
            $table->string('query_normalized');
            $table->json('filters')->nullable();
            $table->unsignedInteger('hits_count');
            $table->foreignId('clicked_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('occurred_at');

            $table->index('query_normalized');
            $table->index('hits_count');
        });

        Schema::create('search_synonyms', function (Blueprint $table) {
            $table->id();
            $table->string('term')->unique();
            $table->json('synonyms');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_synonyms');
        Schema::dropIfExists('search_logs');
        Schema::dropIfExists('behavior_events');
        Schema::dropIfExists('visitor_sessions');
    }
};
