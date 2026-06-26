<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sert les URLs Drupal legacy :
 *   /{drupalId}/{slug}                    ex: /484/novophane-shampooing-energisant-f-200ml
 *   /{drupalId}/{brandId}/{slug}          ex: /25/3/acm-novophane-shampooing-revitalisant-200ml
 *
 * Le `canonical_path` stocké en DB sert de source de vérité. Si l'URL demandée
 * ne correspond pas exactement (slug modifié, brandId inversé), on redirige
 * en 301 vers le canonical_path pour consolider le SEO.
 */
class LegacyProductController extends Controller
{
    public function show(Request $request, int $drupalId, string $slugOrBrand, ?string $slug = null): View|Response
    {
        $product = Product::query()
            ->where('legacy_drupal_id', $drupalId)
            ->with(['brand', 'categories', 'variants.images', 'images'])
            ->first();

        if (! $product || $product->status !== 'published') {
            abort(404);
        }

        // Si l'URL demandée diffère du canonical_path enregistré → 301
        $requested = '/'.ltrim($request->path(), '/');
        if ($requested !== $product->canonical_path) {
            return redirect($product->canonical_path, 301);
        }

        $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();

        return view('catalog::products.show', [
            'product' => $product,
            'variant' => $variant,
        ]);
    }
}
