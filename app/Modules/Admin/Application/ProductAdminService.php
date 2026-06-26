<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application;

use App\Models\User;
use App\Modules\Catalog\Domain\Models\Brand;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Media;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\Variant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductAdminService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $userId = null): Product
    {
        return DB::transaction(function () use ($data, $userId) {
            $slug = Product::generateUniqueNewSlug($data['title']);

            $product = Product::create([
                'origin' => 'backoffice',
                'slug' => $slug,
                'canonical_path' => '/produits/'.$slug,
                'langcode' => 'fr',
                'brand_id' => $data['brand_id'] ?? null,
                'title' => $data['title'],
                'short_description_html' => $data['short_description_html'] ?? null,
                'description_html' => $data['description_html'] ?? null,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'status' => 'draft',
                'featured' => (bool) ($data['featured'] ?? false),
                'created_by_user_id' => $userId,
                'search_index_dirty' => true,
            ]);

            if (! empty($data['category_ids'])) {
                $product->categories()->sync($data['category_ids']);
            }

            Variant::create([
                'product_id' => $product->id,
                'sku' => $data['sku'] ?? 'SKU-'.Str::upper(Str::random(8)),
                'title' => $data['variant_title'] ?? null,
                'price_minor' => (int) ($data['price_minor'] ?? 0),
                'currency_code' => 'XAF',
                'stock' => isset($data['stock']) ? (int) $data['stock'] : null,
                'is_default' => true,
                'status' => 'active',
            ]);

            return $product->fresh(['brand', 'categories', 'variants', 'images']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update([
                'brand_id' => $data['brand_id'] ?? null,
                'title' => $data['title'],
                'short_description_html' => $data['short_description_html'] ?? null,
                'description_html' => $data['description_html'] ?? null,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'featured' => (bool) ($data['featured'] ?? false),
                'search_index_dirty' => true,
            ]);

            if (array_key_exists('category_ids', $data)) {
                $product->categories()->sync($data['category_ids'] ?? []);
            }

            $variant = $product->variants()->where('is_default', true)->first()
                ?? $product->variants()->first();

            if ($variant) {
                $variant->update([
                    'sku' => $data['sku'] ?? $variant->sku,
                    'title' => $data['variant_title'] ?? $variant->title,
                    'price_minor' => (int) ($data['price_minor'] ?? $variant->price_minor),
                    'stock' => array_key_exists('stock', $data) ? (int) $data['stock'] : $variant->stock,
                ]);
            }

            return $product->fresh(['brand', 'categories', 'variants', 'images']);
        });
    }

    public function publish(Product $product): Product
    {
        $product->publish();
        $product->search_index_dirty = true;
        $product->save();

        return $product;
    }

    public function archive(Product $product): Product
    {
        $product->status = 'archived';
        $product->search_index_dirty = true;
        $product->save();

        return $product;
    }

    public function attachImage(Product $product, UploadedFile $file, int $position = 0): Media
    {
        $this->assertImage($file);

        $filename = Str::uuid().'.'.$file->guessExtension();
        $path = $file->storeAs('products/backoffice', $filename, 'public');

        $media = Media::create([
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'bytes' => $file->getSize(),
            'alt_text' => $product->title,
            'is_available' => true,
        ]);

        $role = $product->images()->count() === 0 ? 'primary' : 'gallery';
        $product->images()->attach($media->id, [
            'position' => $position,
            'role' => $role,
        ]);

        $product->search_index_dirty = true;
        $product->save();

        return $media;
    }

    public function detachImage(Product $product, Media $media): void
    {
        $product->images()->detach($media->id);
        $product->search_index_dirty = true;
        $product->save();
    }

    private function assertImage(UploadedFile $file): void
    {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (! in_array($file->getMimeType(), $allowed, true)) {
            throw new \InvalidArgumentException('Format d\'image non supporté.');
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Image trop volumineuse (max 5 Mo).');
        }
    }
}
