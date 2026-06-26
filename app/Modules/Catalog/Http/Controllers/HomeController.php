<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Domain\Models\Brand;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured = Product::published()
            ->where('featured', true)
            ->with(['brand', 'variants', 'images'])
            ->latest('published_at')
            ->limit(12)
            ->get();

        $latest = Product::published()
            ->with(['brand', 'variants', 'images'])
            ->latest('published_at')
            ->limit(24)
            ->get();

        $brands = Brand::query()
            ->whereHas('products', fn ($q) => $q->where('status', 'published'))
            ->orderBy('name')
            ->limit(24)
            ->get();

        $categories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('catalog::home', compact('featured', 'latest', 'brands', 'categories'));
    }
}
