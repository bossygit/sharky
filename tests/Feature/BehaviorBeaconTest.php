<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Behavior\Domain\Models\BehaviorEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BehaviorBeaconTest extends TestCase
{
    use RefreshDatabase;

    public function test_beacon_records_client_events(): void
    {
        $this->postJson('/api/behavior/beacon', [
            'events' => [
                ['type' => 'scroll_depth', 'payload' => ['percent' => 50]],
                ['type' => 'rage_click', 'payload' => ['x' => 10, 'y' => 20]],
            ],
        ])->assertNoContent();

        $this->assertSame(2, BehaviorEvent::count());
        $this->assertDatabaseHas('domain_events', ['type' => 'scroll_depth']);
    }
}
