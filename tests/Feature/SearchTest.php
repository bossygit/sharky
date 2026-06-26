<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Search\Domain\Models\SearchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_lists_matching_products(): void
    {
        Product::create([
            'uuid' => (string) Str::uuid(),
            'origin' => 'backoffice',
            'canonical_path' => '/produits/avene-test',
            'slug' => 'avene-test',
            'title' => 'Avene Eau Thermale',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get(route('search', ['q' => 'Avene']))
            ->assertOk()
            ->assertSee('Avene Eau Thermale');

        $this->assertDatabaseHas('search_logs', [
            'query' => 'Avene',
            'hits_count' => 1,
        ]);
    }

    public function test_zero_result_search_is_logged(): void
    {
        $this->get(route('search', ['q' => 'xyzinexistant']))
            ->assertOk()
            ->assertSee('Aucun résultat');

        $this->assertSame(0, SearchLog::first()->hits_count);
    }
}
