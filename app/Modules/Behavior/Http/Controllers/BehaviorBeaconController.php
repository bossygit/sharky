<?php

declare(strict_types=1);

namespace App\Modules\Behavior\Http\Controllers;

use App\Modules\Behavior\Application\BehaviorTracker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class BehaviorBeaconController extends Controller
{
    public function __construct(private BehaviorTracker $tracker) {}

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'events' => ['required', 'array', 'max:20'],
            'events.*.type' => ['required', 'string', 'max:64'],
            'events.*.payload' => ['nullable', 'array'],
            'events.*.product_id' => ['nullable', 'integer'],
        ]);

        foreach ($data['events'] as $event) {
            $this->tracker->track(
                type: $event['type'],
                productId: $event['product_id'] ?? null,
                payload: $event['payload'] ?? null,
                url: $request->headers->get('Referer'),
            );
        }

        return response()->noContent();
    }
}
