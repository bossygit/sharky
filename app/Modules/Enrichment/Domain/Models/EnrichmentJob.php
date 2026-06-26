<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Domain\Models;

use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnrichmentJob extends Model
{
    protected $fillable = [
        'product_id',
        'status',
        'attempts',
        'started_at',
        'completed_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<EnrichmentDraft, $this> */
    public function drafts(): HasMany
    {
        return $this->hasMany(EnrichmentDraft::class, 'job_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['queued', 'running', 'draft'], true);
    }
}
