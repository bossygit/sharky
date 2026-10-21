<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Domain\Models\Brand;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Media;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\Variant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Catalogue de démonstration de la cave Nasande Vins.
 *
 * Les données vivent dans database/data/vins.json et les visuels SVG sont
 * générés par tools/generate-wine-assets.py. Le seeder est idempotent :
 * on peut le relancer après un déploiement sans créer de doublons.
 */
class VinsCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Sur un environnement déjà en production, on ne réécrase jamais les
        // prix/stocks modifiés dans l'admin. VINS_SEED_FORCE=1 pour rejouer.
        if (! env('VINS_SEED_FORCE') && Product::query()->exists()) {
            $this->command?->info('Catalogue déjà en place — seeding ignoré (VINS_SEED_FORCE=1 pour forcer).');

            return;
        }

        $data = json_decode((string) file_get_contents(database_path('data/vins.json')), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data) {
            $categories = [];
            foreach ($data['categories'] as $category) {
                $categories[$category['slug']] = Category::updateOrCreate(
                    ['slug' => $category['slug']],
                    [
                        'name' => $category['name'],
                        'description' => $category['description'],
                        'sort_order' => $category['sort_order'],
                        'meta_title' => $category['name'].' — Nasande Vins',
                        'meta_description' => Str::limit(strip_tags($category['description']), 155),
                    ]
                );
            }

            foreach ($data['wines'] as $wine) {
                $brand = Brand::updateOrCreate(
                    ['slug' => Str::slug($wine['maison'])],
                    ['name' => $wine['maison']]
                );

                $this->seedWine($wine, $brand, $categories[$wine['categorie']] ?? null);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $wine
     */
    private function seedWine(array $wine, Brand $brand, ?Category $category): void
    {
        $title = trim($wine['maison'].' '.$wine['nom']);

        $specifications = [
            'type' => $wine['type'],
            'millesime' => $wine['millesime'],
            'region' => $wine['region'],
            'pays' => $wine['pays'],
            'appellation' => $wine['appellation'],
            'cepage' => $wine['cepage'],
            'alcool' => $wine['alcool'],
            'format' => $wine['format'],
            'service' => $wine['service'],
            'garde' => $wine['garde'],
            'notes_degustation' => $wine['notes_degustation'],
            'accords' => $wine['accords'],
            'note' => $wine['note'],
            'note_source' => $wine['note_source'],
            'categorie' => $wine['categorie'],
        ];

        $product = Product::updateOrCreate(
            ['slug' => $wine['slug']],
            [
                'origin' => 'backoffice',
                'canonical_path' => '/produits/'.$wine['slug'],
                'langcode' => 'fr',
                'brand_id' => $brand->id,
                'title' => $title,
                'short_description_html' => '<p>'.e($wine['notes_degustation']).'</p>',
                'description_html' => '<p>'.e($wine['maison'].' '.$wine['nom']).' — '.e($wine['appellation']).', '.e($wine['region']).' ('.e($wine['pays']).').</p><p>'.e($wine['notes_degustation']).'</p><p><strong>À table :</strong> '.e($wine['accords']).'</p>',
                'specifications' => $specifications,
                'meta_title' => $title.' '.$wine['millesime'].' — Nasande Vins',
                'meta_description' => Str::limit($wine['notes_degustation'], 155),
                'status' => 'published',
                'published_at' => now(),
                'featured' => (bool) $wine['featured'],
                'ai_enrichment_status' => 'done',
                'search_index_dirty' => true,
            ]
        );

        if ($category) {
            $product->categories()->syncWithoutDetaching([$category->id]);
        }

        $variant = Variant::updateOrCreate(
            ['sku' => $this->sku($wine)],
            [
                'product_id' => $product->id,
                'title' => $wine['format'],
                'price_minor' => (int) $wine['prix'],
                'currency_code' => 'XAF',
                'stock' => (int) $wine['stock'],
                'weight_grams' => 1300,
                'options' => ['volume' => $wine['format']],
                'is_default' => true,
                'status' => 'active',
            ]
        );

        $media = Media::updateOrCreate(
            ['disk' => 'assets', 'path' => 'images/vins/'.$wine['slug'].'.svg'],
            [
                'mime_type' => 'image/svg+xml',
                'width' => 600,
                'height' => 800,
                'bytes' => @filesize(public_path('images/vins/'.$wine['slug'].'.svg')) ?: null,
                'alt_text' => $title,
                'title' => $title,
                'is_available' => true,
            ]
        );

        $product->images()->syncWithoutDetaching([
            $media->id => ['position' => 0, 'role' => 'primary'],
        ]);
        $variant->images()->syncWithoutDetaching([$media->id => ['position' => 0]]);
    }

    /**
     * @param  array<string, mixed>  $wine
     */
    private function sku(array $wine): string
    {
        return 'NV-'.Str::upper(Str::slug($wine['slug'], '-'));
    }
}
