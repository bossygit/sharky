<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demand_clusters', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->unsignedInteger('member_count')->default(0);
            $table->json('ngram_signature')->nullable();
            $table->timestamps();
        });

        Schema::create('demand_signals', function (Blueprint $table) {
            $table->id();
            $table->string('keyword')->unique();
            $table->foreignId('cluster_id')->nullable()->constrained('demand_clusters')->nullOnDelete();
            $table->unsignedInteger('frequency_7d')->default(0);
            $table->unsignedInteger('frequency_30d')->default(0);
            $table->unsignedInteger('unique_sessions_7d')->default(0);
            $table->decimal('intent_score', 4, 3)->nullable();
            $table->string('urgency', 16)->default('low');
            $table->json('example_queries')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('status', 16)->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'urgency']);
            $table->index(['cluster_id', 'frequency_7d']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_signals');
        Schema::dropIfExists('demand_clusters');
    }
};
