<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Variant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CategoryController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $categories = Category::query()
            ->whereNull('parent_id')
            ->withCount(['products as published_products_count' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $sort = (string) $request->query('tri', 'selection');
        $sort = in_array($sort, ['selection', 'prix-asc', 'prix-desc', 'nouveautes'], true) ? $sort : 'selection';

        $query = $category->products()
            ->where('status', 'published')
            ->with(['brand', 'variants', 'images']);

        $priceSubquery = Variant::query()
            ->select('price_minor')
            ->whereColumn('variants.product_id', 'products.id')
            ->orderByDesc('is_default')
            ->limit(1);

        match ($sort) {
            'prix-asc' => $query->orderBy($priceSubquery),
            'prix-desc' => $query->orderByDesc($priceSubquery),
            'nouveautes' => $query->orderByDesc('published_at'),
            default => $query->orderByDesc('featured')->orderByDesc('published_at'),
        };

        $products = $query->paginate(12)->withQueryString();

        return view('catalog::categories.show', compact('category', 'products', 'categories', 'sort'));
    }
}
