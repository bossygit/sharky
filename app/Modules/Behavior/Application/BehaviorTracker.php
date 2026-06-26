<?php

declare(strict_types=1);

namespace App\Modules\Behavior\Application;

use App\Modules\Behavior\Domain\Models\BehaviorEvent;
use App\Modules\Observability\Application\DomainEventRecorder;
use Illuminate\Support\Facades\Auth;

class BehaviorTracker
{
    public function __construct(
        private VisitorSessionService $sessions,
        private DomainEventRecorder $events,
    ) {}

    /** @param  array<string, mixed>|null  $payload */
    public function track(
        string $type,
        ?int $productId = null,
        ?array $payload = null,
        ?string $url = null,
    ): void {
        try {
            if ($this->sessions->isBot()) {
                return;
            }

            $session = $this->sessions->resolve();

            BehaviorEvent::create([
                'session_id' => $session->id,
                'user_id' => Auth::id(),
                'type' => $type,
                'url' => $url,
                'product_id' => $productId,
                'payload' => $payload,
                'occurred_at' => now(),
            ]);

            $this->events->record(
                aggregateType: 'visitor_session',
                aggregateId: $session->id,
                type: $type,
                payload: array_filter([
                    'product_id' => $productId,
                    'url' => $url,
                    'data' => $payload,
                ]),
                metadata: ['user_id' => Auth::id()],
            );
        } catch (\Throwable) {
            // Le tracking ne doit jamais casser une page publique.
        }
    }
}
