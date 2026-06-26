<?php

declare(strict_types=1);

namespace App\Modules\Demand\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandSignal extends Model
{
    protected $fillable = [
        'keyword',
        'cluster_id',
        'frequency_7d',
        'frequency_30d',
        'unique_sessions_7d',
        'intent_score',
        'urgency',
        'example_queries',
        'first_seen_at',
        'last_seen_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'example_queries' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DemandCluster, $this> */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(DemandCluster::class, 'cluster_id');
    }

    public function urgencyScore(): int
    {
        return match ($this->urgency) {
            'viral' => 4,
            'high' => 3,
            'medium' => 2,
            default => 1,
        };
    }
}
