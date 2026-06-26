<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrichment_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('status', 16)->default('queued');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });

        Schema::create('enrichment_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->nullable()->constrained('enrichment_jobs')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('field');
            $table->json('value_proposed');
            $table->json('value_current')->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->json('sources')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('decision', 16)->default('pending');
            $table->timestamps();

            $table->index(['product_id', 'decision']);
            $table->index(['job_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrichment_drafts');
        Schema::dropIfExists('enrichment_jobs');
    }
};
