<?php

declare(strict_types=1);

namespace App\Modules\Enrichment\Domain\Models;

use App\Models\User;
use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrichmentDraft extends Model
{
    protected $fillable = [
        'job_id',
        'product_id',
        'field',
        'value_proposed',
        'value_current',
        'confidence',
        'sources',
        'reviewer_id',
        'reviewed_at',
        'decision',
    ];

    protected function casts(): array
    {
        return [
            'value_proposed' => 'array',
            'value_current' => 'array',
            'sources' => 'array',
            'confidence' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<EnrichmentJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(EnrichmentJob::class, 'job_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function proposedScalar(): mixed
    {
        $value = $this->value_proposed;

        return is_array($value) && array_key_exists('value', $value) ? $value['value'] : $value;
    }

    public function currentScalar(): mixed
    {
        $value = $this->value_current;

        return is_array($value) && array_key_exists('value', $value) ? $value['value'] : $value;
    }
}
