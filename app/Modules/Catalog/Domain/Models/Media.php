<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Models;

use App\Modules\Catalog\Application\ImageVariantService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'legacy_drupal_fid',
        'disk',
        'path',
        'mime_type',
        'width',
        'height',
        'bytes',
        'alt_text',
        'title',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_media')
            ->withPivot(['position', 'role'])
            ->orderByPivot('position');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(Variant::class, 'variant_media')
            ->withPivot(['position'])
            ->orderByPivot('position');
    }

    /**
     * URL publique de l'image.
     *
     * On retourne un chemin relatif (`/storage/...`) pour que les images
     * s'affichent correctement quels que soient le port/host de dev et
     * sans configuration `APP_URL` parfaite. En production, un CDN ou
     * un préfixe d'URL pourra être appliqué via un override.
     */
    public function getUrlAttribute(): ?string
    {
        if (! $this->is_available) {
            return null;
        }

        if ($this->disk === 'public') {
            return '/storage/'.ltrim($this->path, '/');
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function srcset(): ?string
    {
        if (! $this->is_available || $this->url === null) {
            return null;
        }

        $parts = [];
        if ($this->disk === 'public' && $this->path) {
            $service = app(ImageVariantService::class);
            foreach (ImageVariantService::WIDTHS as $width) {
                $variant = $service->variantRelativePath($this->path, $width);
                if (Storage::disk('public')->exists($variant)) {
                    $parts[] = '/storage/'.ltrim($variant, '/').' '.$width.'w';
                }
            }
        }

        $parts[] = $this->url.' '.($this->width ?: 1600).'w';

        return implode(', ', $parts);
    }

    public function sizes(string $context = 'card'): string
    {
        return $context === 'detail'
            ? '(max-width: 768px) 100vw, 50vw'
            : '(max-width: 640px) 50vw, 220px';
    }
}
