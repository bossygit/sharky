<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class NewProductController extends Controller
{
    public function show(string $slug): View
    {
        $product = Product::query()
            ->where('origin', '!=', 'drupal_migration')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with(['brand', 'categories', 'variants.images', 'images'])
            ->firstOrFail();

        $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();

        $categoryIds = $product->categories->pluck('id');

        $related = Product::published()
            ->whereKeyNot($product->id)
            ->when($categoryIds->isNotEmpty(), fn ($q) => $q->whereHas(
                'categories',
                fn ($c) => $c->whereIn('categories.id', $categoryIds)
            ))
            ->with(['brand', 'variants', 'images'])
            ->orderByDesc('featured')
            ->limit(4)
            ->get();

        return view('catalog::products.show', compact('product', 'variant', 'related'));
    }
}
