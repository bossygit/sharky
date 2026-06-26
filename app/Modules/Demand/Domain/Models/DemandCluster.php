<?php

declare(strict_types=1);

namespace App\Modules\Demand\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemandCluster extends Model
{
    protected $fillable = [
        'label',
        'member_count',
        'ngram_signature',
    ];

    protected function casts(): array
    {
        return [
            'ngram_signature' => 'array',
        ];
    }

    /** @return HasMany<DemandSignal, $this> */
    public function signals(): HasMany
    {
        return $this->hasMany(DemandSignal::class, 'cluster_id');
    }

    public function frequency7d(): int
    {
        return (int) $this->signals()->sum('frequency_7d');
    }

    public function urgencyScore(): int
    {
        return match ($this->signals()->max('urgency')) {
            'viral' => 4,
            'high' => 3,
            'medium' => 2,
            default => 1,
        };
    }
}
