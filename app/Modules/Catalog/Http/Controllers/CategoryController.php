<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Domain\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class CategoryController extends Controller
{
    public function show(string $slug): View
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $products = $category->products()
            ->where('status', 'published')
            ->with(['brand', 'variants', 'images'])
            ->orderByDesc('published_at')
            ->paginate(24);

        return view('catalog::categories.show', compact('category', 'products'));
    }
}
