<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Domain\Models\Brand;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Media;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\Variant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Importe le catalogue Drupal Commerce (export JSON + images) vers la nouvelle DB Laravel.
 *
 * Idempotent : ré-exécutable. Utilise legacy_drupal_id comme clé d'unicité partout.
 *
 *   php artisan migrate:from-drupal
 *   php artisan migrate:from-drupal --skip-images   (debug rapide sans copie d'images)
 *   php artisan migrate:from-drupal --fresh         (truncate puis recharge)
 */
class MigrateFromDrupalCommand extends Command
{
    protected $signature = 'migrate:from-drupal
        {--fresh : Vide les tables catalog avant import}
        {--skip-images : N\'importe pas les fichiers images sur disque}';

    protected $description = 'Ingère les produits Drupal exportés dans out/ vers la base Laravel';

    private string $exportDir;

    private array $stats = [
        'brands' => 0,
        'categories' => 0,
        'products' => 0,
        'variants' => 0,
        'media' => 0,
        'product_media_links' => 0,
        'variant_media_links' => 0,
        'images_copied' => 0,
        'images_skipped' => 0,
        'images_missing' => 0,
    ];

    public function handle(): int
    {
        $this->exportDir = rtrim(base_path(env('DRUPAL_EXPORT_DIR', './out')), '/');

        if (! is_dir($this->exportDir)) {
            $this->error("Dossier d'export introuvable : {$this->exportDir}");

            return self::FAILURE;
        }

        $products = $this->loadJson('products.json');
        $brands = $this->loadJson('brands.json');
        $categories = $this->loadJson('categories.json');

        $this->info('Source : '.$this->exportDir);
        $this->info(sprintf('  • %d produits, %d marques, %d catégories', count($products), count($brands), count($categories)));

        if ($this->option('fresh')) {
            $this->warn('Mode --fresh : truncate des tables catalog.');
            // MySQL ne supporte pas RESTART IDENTITY CASCADE (PostgreSQL uniquement)
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            DB::statement('TRUNCATE TABLE product_categories');
            DB::statement('TRUNCATE TABLE product_media');
            DB::statement('TRUNCATE TABLE variant_media');
            DB::statement('TRUNCATE TABLE variants');
            DB::statement('TRUNCATE TABLE products');
            DB::statement('TRUNCATE TABLE media');
            DB::statement('TRUNCATE TABLE categories');
            DB::statement('TRUNCATE TABLE brands');
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        DB::transaction(function () use ($brands, $categories, $products) {
            $brandIdMap = $this->importBrands($brands);
            $categoryIdMap = $this->importCategories($categories);
            [$productIdMap, $mediaIdMap] = $this->importProductsAndMedia($products, $brandIdMap, $categoryIdMap);

            $this->info("\nLiens produit ↔ média : {$this->stats['product_media_links']}");
            $this->info("Liens variant ↔ média : {$this->stats['variant_media_links']}");

            unset($productIdMap, $mediaIdMap);
        });

        if (! $this->option('skip-images')) {
            $this->copyImagesToStorage();
        }

        $this->newLine();
        $this->info('───── Récapitulatif ─────');
        foreach ($this->stats as $k => $v) {
            $this->line(sprintf('  %-22s %d', $k, $v));
        }

        return self::SUCCESS;
    }

    // ─── Brands ──────────────────────────────────────────────────────────────

    private function importBrands(array $brands): array
    {
        $bar = $this->output->createProgressBar(count($brands));
        $bar->setFormat('  brands  %current%/%max% %bar% %elapsed:6s%');
        $bar->start();

        $idMap = [];
        foreach ($brands as $b) {
            $drupalId = $b['drupal_id'] ?? null;
            if (! $drupalId) {
                continue;
            }

            $brand = Brand::updateOrCreate(
                ['legacy_drupal_id' => $drupalId],
                [
                    'name' => $b['name'],
                    'slug' => Str::slug($b['path'] ? basename($b['path']) : $b['name']),
                    'description' => $b['description'] ?: null,
                    'published_at' => now(),
                ],
            );

            $idMap[$drupalId] = $brand->id;
            $this->stats['brands']++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $idMap;
    }

    // ─── Categories ─────────────────────────────────────────────────────────

    private function importCategories(array $categories): array
    {
        $bar = $this->output->createProgressBar(count($categories));
        $bar->setFormat('  cats    %current%/%max% %bar% %elapsed:6s%');
        $bar->start();

        $idMap = [];
        foreach ($categories as $c) {
            $drupalId = $c['drupal_id'] ?? null;
            if (! $drupalId) {
                continue;
            }

            $category = Category::updateOrCreate(
                ['legacy_drupal_id' => $drupalId],
                [
                    'name' => $c['name'],
                    'slug' => $c['path'] ? basename($c['path']) : Str::slug($c['name']),
                    'description' => $c['description'] ?: null,
                ],
            );

            $idMap[$drupalId] = $category->id;
            $this->stats['categories']++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $idMap;
    }

    // ─── Products + Variants + Media ────────────────────────────────────────

    private function importProductsAndMedia(array $products, array $brandIdMap, array $categoryIdMap): array
    {
        $bar = $this->output->createProgressBar(count($products));
        $bar->setFormat('  prods   %current%/%max% %bar% %elapsed:6s% %message%');
        $bar->setMessage('');
        $bar->start();

        $productIdMap = [];
        $mediaIdMap = [];

        foreach ($products as $p) {
            $drupalId = $p['drupal_id'] ?? null;
            if (! $drupalId) {
                continue;
            }

            $bar->setMessage(Str::limit($p['title'] ?? '', 40));

            $product = Product::updateOrCreate(
                ['legacy_drupal_id' => $drupalId],
                [
                    'origin' => 'drupal_migration',
                    'canonical_path' => $p['path'],
                    'slug' => basename($p['path']),
                    'langcode' => $p['langcode'] ?? 'fr',
                    'brand_id' => isset($p['brand']['drupal_id']) ? ($brandIdMap[$p['brand']['drupal_id']] ?? null) : null,
                    'title' => $p['title'],
                    'short_description_html' => $p['short_description']['html'] ?? null,
                    'description_html' => $p['body']['html'] ?? null,
                    'meta_title' => $p['title'],
                    'meta_description' => $this->buildMetaDescription($p),
                    'status' => $p['status'] ? 'published' : 'archived',
                    'published_at' => $p['status'] ? ($p['created'] ?? now()) : null,
                    'featured' => (bool) ($p['featured'] ?? false),
                    'ai_enrichment_status' => 'pending',
                    'search_index_dirty' => true,
                ],
            );
            $productIdMap[$drupalId] = $product->id;
            $this->stats['products']++;

            // Catégories
            $catIds = [];
            foreach ($p['categories'] ?? [] as $c) {
                if (isset($c['drupal_id'], $categoryIdMap[$c['drupal_id']])) {
                    $catIds[] = $categoryIdMap[$c['drupal_id']];
                }
            }
            $product->categories()->sync($catIds);

            // Variations + Media des variations
            $productImageIds = []; // pour aussi attacher au produit
            foreach ($p['variations'] ?? [] as $idx => $v) {
                $vDrupalId = $v['drupal_id'] ?? null;
                if (! $vDrupalId) {
                    continue;
                }

                $sku = $v['sku'] ?? 'NOSKU-'.$vDrupalId;
                // Dédup : si le SKU existe déjà chez une autre variation legacy,
                // on le suffixe avec son drupal_id pour préserver l'unicité globale.
                $skuTaken = Variant::where('sku', $sku)
                    ->where('legacy_drupal_id', '!=', $vDrupalId)
                    ->exists();
                if ($skuTaken) {
                    $sku = $sku.'-'.$vDrupalId;
                }

                $variant = Variant::updateOrCreate(
                    ['legacy_drupal_id' => $vDrupalId],
                    [
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'title' => $v['title'] ?? null,
                        'price_minor' => $this->parsePriceMinor($v['price'] ?? null),
                        'currency_code' => $v['price']['currency_code'] ?? 'XAF',
                        'list_price_minor' => $v['list_price']['number'] ?? null
                            ? (int) round((float) $v['list_price']['number'])
                            : null,
                        'stock' => $v['stock'] ?? null,
                        'weight_grams' => isset($v['weight']['number'])
                            ? (int) round((float) $v['weight']['number'])
                            : null,
                        'options' => array_filter([
                            'color' => $v['attributes']['color']['name'] ?? null,
                            'size' => $v['attributes']['size']['name'] ?? null,
                            'volume' => $v['attributes']['volume']['name'] ?? null,
                        ]),
                        'is_default' => $idx === 0 || ($p['default_variation_id'] ?? null) === ($v['id'] ?? null),
                        'status' => ($v['status'] ?? true) ? 'active' : 'inactive',
                    ],
                );
                $this->stats['variants']++;

                // Images de la variation
                $variantImageIds = [];
                foreach ($v['images'] ?? [] as $pos => $img) {
                    if (! ($img['drupal_id'] ?? null)) {
                        continue;
                    }
                    $media = $this->upsertMedia($img);
                    $mediaIdMap[$img['drupal_id']] = $media->id;
                    $variantImageIds[$media->id] = ['position' => $pos];
                    $productImageIds[$media->id] = ['position' => $pos, 'role' => $pos === 0 ? 'primary' : 'gallery'];
                }
                $variant->images()->sync($variantImageIds);
                $this->stats['variant_media_links'] += count($variantImageIds);
            }

            // Images du produit (agrégat dédoublonné déjà calculé côté export → p.images)
            // mais on les a déjà toutes vues via les variations.
            $product->images()->sync($productImageIds);
            $this->stats['product_media_links'] += count($productImageIds);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return [$productIdMap, $mediaIdMap];
    }

    private function upsertMedia(array $img): Media
    {
        $localFile = $img['local_file'] ?? null;     // ex: images/484_xxx.png
        $pathInStorage = $localFile
            ? 'products/legacy/'.basename($localFile)
            : null;

        $media = Media::updateOrCreate(
            ['legacy_drupal_fid' => $img['drupal_id']],
            [
                'disk' => 'public',
                'path' => $pathInStorage ?? 'products/legacy/unknown',
                'mime_type' => $img['filemime'] ?? null,
                'width' => $img['width'] ?? null,
                'height' => $img['height'] ?? null,
                'bytes' => $img['filesize'] ?? null,
                'alt_text' => $img['alt'] ?: null,
                'title' => $img['title'] ?: null,
                'is_available' => (bool) ($img['available'] ?? true),
            ],
        );

        if ($media->wasRecentlyCreated || $media->wasChanged()) {
            $this->stats['media']++;
        }

        return $media;
    }

    // ─── Copie des images ───────────────────────────────────────────────────

    private function copyImagesToStorage(): void
    {
        $srcDir = $this->exportDir.'/images';
        $dstDir = storage_path('app/public/products/legacy');

        if (! is_dir($srcDir)) {
            $this->warn("Pas de dossier d'images source : {$srcDir}");

            return;
        }

        File::ensureDirectoryExists($dstDir);

        $files = glob($srcDir.'/*') ?: [];
        $bar = $this->output->createProgressBar(count($files));
        $bar->setFormat('  images  %current%/%max% %bar% %elapsed:6s%');
        $bar->start();

        foreach ($files as $src) {
            $dst = $dstDir.'/'.basename($src);
            if (file_exists($dst) && filesize($dst) === filesize($src)) {
                $this->stats['images_skipped']++;
            } else {
                copy($src, $dst);
                $this->stats['images_copied']++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function loadJson(string $name): array
    {
        $path = "{$this->exportDir}/{$name}";
        if (! is_file($path)) {
            throw new RuntimeException("Fichier introuvable : {$path}");
        }

        $data = json_decode(file_get_contents($path) ?: '', true);
        if (! is_array($data)) {
            throw new RuntimeException("JSON invalide : {$path}");
        }

        return $data;
    }

    private function parsePriceMinor(?array $price): int
    {
        if (! $price || ! isset($price['number'])) {
            return 0;
        }

        // XAF : pas de décimale, mais Drupal stocke "12500.000000". On round.
        return (int) round((float) $price['number']);
    }

    private function buildMetaDescription(array $product): ?string
    {
        $shortRaw = strip_tags($product['short_description']['html'] ?? '');
        $bodyRaw = strip_tags($product['body']['html'] ?? '');
        $base = trim($shortRaw) ?: trim($bodyRaw);
        if ($base === '') {
            return null;
        }

        // 160 chars max + ellipsis
        return Str::limit(preg_replace('/\s+/', ' ', $base), 157);
    }
}
