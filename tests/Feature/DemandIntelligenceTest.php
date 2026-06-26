<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Demand\Domain\Models\DemandCluster;
use App\Modules\Demand\Domain\Models\DemandSignal;
use App\Modules\Search\Domain\Models\SearchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemandIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_result_search_creates_demand_signal(): void
    {
        $this->get(route('search', ['q' => 'chargeur iphone inconnu']))
            ->assertOk();

        $this->assertDatabaseHas('demand_signals', [
            'keyword' => 'chargeur iphone inconnu',
        ]);
    }

    public function test_projection_clusters_similar_queries(): void
    {
        $this->seedZeroResultLog('chargeur iphone', 'chargeur iphone 15');
        $this->seedZeroResultLog('chargeur iphon', 'chargeur iphon 14');

        $this->artisan('demand:project-clusters')->assertSuccessful();

        $this->assertGreaterThanOrEqual(1, DemandCluster::count());
        $this->assertGreaterThanOrEqual(2, DemandSignal::count());

        $clusterIds = DemandSignal::pluck('cluster_id')->filter()->unique();
        $this->assertSame(1, $clusterIds->count());
    }

    public function test_admin_can_view_demand_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        DemandSignal::create([
            'keyword' => 'creme bebe',
            'frequency_7d' => 3,
            'frequency_30d' => 3,
            'urgency' => 'medium',
            'status' => 'open',
            'example_queries' => ['crème bébé'],
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.demand.index'))
            ->assertOk()
            ->assertSee('Demande non satisfaite')
            ->assertSee('Signaux ouverts');
    }

    public function test_admin_can_source_cluster(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cluster = DemandCluster::create(['label' => 'Test cluster', 'member_count' => 1]);
        $signal = DemandSignal::create([
            'keyword' => 'test keyword',
            'cluster_id' => $cluster->id,
            'frequency_7d' => 1,
            'status' => 'open',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.demand.clusters.source', $cluster))
            ->assertRedirect();

        $this->assertSame('sourced', $signal->fresh()->status);
    }

    private function seedZeroResultLog(string $normalized, string $raw): void
    {
        SearchLog::create([
            'session_id' => (string) Str::uuid(),
            'query' => $raw,
            'query_normalized' => $normalized,
            'hits_count' => 0,
            'occurred_at' => now(),
        ]);
    }
}
