<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'provider_reference',
        'amount_minor',
        'status',
        'idempotency_key',
        'payload',
        'webhook_received_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'payload' => 'array',
            'webhook_received_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
