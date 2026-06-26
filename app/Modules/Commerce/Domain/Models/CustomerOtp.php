<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOtp extends Model
{
    protected $fillable = [
        'email',
        'code_hash',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
