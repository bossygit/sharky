<?php

declare(strict_types=1);

namespace App\Modules\Observability\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class DomainEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'aggregate_type',
        'aggregate_id',
        'type',
        'payload',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
