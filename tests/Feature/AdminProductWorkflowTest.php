<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Application\ProductAdminService;
use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_publish_backoffice_product(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.products.store'), [
            'title' => 'Crème test admin',
            'price_minor' => 9900,
            'sku' => 'ADM-001',
            'short_description_html' => '<p>Test</p>',
        ]);

        $response->assertRedirect();

        $product = Product::where('title', 'Crème test admin')->first();
        $this->assertNotNull($product);
        $this->assertSame('backoffice', $product->origin);
        $this->assertSame('draft', $product->status);
        $this->assertStringStartsWith('/produits/', $product->canonical_path);

        $this->post(route('admin.products.publish', $product))
            ->assertRedirect();

        $product->refresh();
        $this->assertSame('published', $product->status);

        $this->get('/produits/'.$product->slug)
            ->assertOk()
            ->assertSee('Crème test admin')
            ->assertSee('application/ld+json', false);
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }
}
