<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Enrichment\Application\EnrichmentOrchestrator;
use App\Modules\Enrichment\Domain\Models\EnrichmentDraft;
use App\Modules\Enrichment\Domain\Models\EnrichmentJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnrichmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrichment_pipeline_creates_drafts(): void
    {
        $product = $this->makeProduct();

        app(EnrichmentOrchestrator::class)->enqueue($product);

        $job = EnrichmentJob::first();
        $this->assertNotNull($job);
        $this->assertSame('draft', $job->status);
        $this->assertGreaterThan(0, EnrichmentDraft::where('product_id', $product->id)->count());
        $this->assertSame('review', $product->fresh()->ai_enrichment_status);
    }

    public function test_admin_can_approve_enrichment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();

        app(EnrichmentOrchestrator::class)->enqueue($product);

        $this->actingAs($admin)
            ->post(route('admin.enrichment.approve', $product))
            ->assertRedirect(route('admin.enrichment.index'));

        $product->refresh();
        $this->assertSame('approved', $product->ai_enrichment_status);
        $this->assertNotNull($product->description_html);
        $this->assertNotNull($product->meta_title);
    }

    public function test_admin_can_enqueue_from_product_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();

        $this->actingAs($admin)
            ->post(route('admin.products.enrich', $product))
            ->assertRedirect();

        $this->assertDatabaseHas('enrichment_jobs', ['product_id' => $product->id]);
    }

    public function test_backfill_enqueues_products_without_description(): void
    {
        $this->makeProduct(description: null);

        $this->artisan('enrichment:backfill', ['--limit' => 10])->assertSuccessful();

        $this->assertSame(1, EnrichmentJob::count());
    }

    private function makeProduct(?string $description = ''): Product
    {
        return Product::create([
            'uuid' => (string) Str::uuid(),
            'origin' => 'backoffice',
            'canonical_path' => '/produits/test-'.Str::random(6),
            'slug' => 'test-'.Str::random(6),
            'title' => 'Crème hydratante test',
            'description_html' => $description,
            'status' => 'draft',
        ]);
    }
}
