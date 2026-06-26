<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Domain\Models\Brand;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class BrandController extends Controller
{
    public function show(string $slug): View
    {
        $brand = Brand::where('slug', $slug)->firstOrFail();

        $products = $brand->products()
            ->where('status', 'published')
            ->with(['brand', 'variants', 'images'])
            ->orderByDesc('published_at')
            ->paginate(24);

        return view('catalog::brands.show', compact('brand', 'products'));
    }
}
