<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use NumberFormatter;

class Variant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'variants';

    protected $fillable = [
        'product_id',
        'legacy_drupal_id',
        'sku',
        'title',
        'price_minor',
        'currency_code',
        'list_price_minor',
        'stock',
        'weight_grams',
        'options',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'list_price_minor' => 'integer',
            'stock' => 'integer',
            'weight_grams' => 'integer',
            'options' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'variant_media')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function isInStock(): bool
    {
        return $this->status === 'active' && ($this->stock === null || $this->stock > 0);
    }

    public function availability(): string
    {
        if ($this->status !== 'active') {
            return 'OutOfStock';
        }
        if ($this->stock === null) {
            return 'InStock';
        }
        if ($this->stock <= 0) {
            return 'OutOfStock';
        }
        if ($this->stock < 5) {
            return 'LimitedAvailability';
        }

        return 'InStock';
    }

    /**
     * Formate le prix en FCFA (XAF — pas de décimale).
     */
    public function getFormattedPriceAttribute(): string
    {
        return self::formatXaf($this->price_minor);
    }

    public static function formatXaf(int $minor): string
    {
        $formatter = new NumberFormatter('fr_FR', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);

        return $formatter->format($minor).' FCFA';
    }
}
