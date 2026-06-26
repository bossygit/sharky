<?php

declare(strict_types=1);

namespace App\Modules\Observability\Application;

use App\Modules\Observability\Domain\Models\DomainEvent;

class DomainEventRecorder
{
    /** @param  array<string, mixed>  $payload */
    public function record(
        string $aggregateType,
        string $aggregateId,
        string $type,
        array $payload,
        ?array $metadata = null,
    ): DomainEvent {
        return DomainEvent::create([
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'type' => $type,
            'payload' => $payload,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
