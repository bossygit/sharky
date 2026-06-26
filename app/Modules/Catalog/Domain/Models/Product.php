<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'uuid',
        'legacy_drupal_id',
        'origin',
        'canonical_path',
        'slug',
        'langcode',
        'brand_id',
        'title',
        'short_description_html',
        'description_html',
        'features',
        'specifications',
        'faqs',
        'meta_title',
        'meta_description',
        'status',
        'published_at',
        'created_by_user_id',
        'featured',
        'search_index_dirty',
        'ai_enrichment_status',
        'ai_confidence',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'specifications' => 'array',
            'faqs' => 'array',
            'featured' => 'boolean',
            'search_index_dirty' => 'boolean',
            'published_at' => 'datetime',
            'ai_confidence' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }
        });
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    public function enrichmentDrafts(): HasMany
    {
        return $this->hasMany(\App\Modules\Enrichment\Domain\Models\EnrichmentDraft::class);
    }

    public function defaultVariant(): HasMany
    {
        return $this->variants()->where('is_default', true);
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'product_media')
            ->withPivot(['position', 'role'])
            ->orderByPivot('position');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')->whereNotNull('published_at');
    }

    public function scopeLegacy(Builder $q): Builder
    {
        return $q->where('origin', 'drupal_migration');
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('featured', true);
    }

    // ─── Helpers métier ─────────────────────────────────────────────────────

    public function isLegacy(): bool
    {
        return $this->origin === 'drupal_migration';
    }

    public function url(): string
    {
        return url($this->canonical_path);
    }

    public function primaryImage(): ?Media
    {
        // Image 'primary' explicite, sinon première de la galerie, sinon image
        // de la variation par défaut.
        $img = $this->images
            ->sortBy(fn (Media $m) => $m->pivot->role === 'primary' ? 0 : 1)
            ->first(fn (Media $m) => $m->is_available);

        if ($img) {
            return $img;
        }

        $default = $this->variants->firstWhere('is_default', true) ?? $this->variants->first();

        return $default?->images->firstWhere('is_available', true);
    }

    public function priceMinor(): ?int
    {
        $v = $this->variants->firstWhere('is_default', true) ?? $this->variants->first();

        return $v?->price_minor;
    }

    public function formattedPrice(): ?string
    {
        $price = $this->priceMinor();

        return $price !== null ? Variant::formatXaf($price) : null;
    }

    public function publish(): void
    {
        $this->status = 'published';
        $this->published_at ??= now();
        $this->save();
    }

    /**
     * Génère un slug unique pour un nouveau produit. Ignore les produits legacy
     * (qui peuvent avoir des slugs dupliqués distingués par leur drupal_id).
     */
    public static function generateUniqueNewSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $n = 2;
        while (
            self::query()
                ->where('origin', '<>', 'drupal_migration')
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$n;
            $n++;
        }

        return $slug;
    }

    // ─── Scout / Meilisearch ─────────────────────────────────────────────────

    public function toSearchableArray(): array
    {
        $this->loadMissing(['brand', 'categories', 'variants']);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'canonical_path' => $this->canonical_path,
            'brand_name' => $this->brand?->name,
            'category_names' => $this->categories->pluck('name')->values()->all(),
            'description_text' => trim(strip_tags((string) ($this->short_description_html ?: $this->description_html ?: ''))),
            'sku' => $this->variants->pluck('sku')->filter()->values()->all(),
            'price_minor' => $this->priceMinor(),
            'status' => $this->status,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    public function searchableAs(): string
    {
        return 'products';
    }
}
