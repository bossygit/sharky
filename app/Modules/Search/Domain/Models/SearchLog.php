<?php

declare(strict_types=1);

namespace App\Modules\Search\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'query',
        'query_normalized',
        'filters',
        'hits_count',
        'clicked_product_id',
        'duration_ms',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
