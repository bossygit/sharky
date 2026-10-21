<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

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
            ->limit(8)
            ->get();

        $categories = Category::query()
            ->whereNull('parent_id')
            ->withCount(['products as published_products_count' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Sélection du sommelier : les bouteilles les mieux notées par les critiques.
        // Filtrage en mémoire — le catalogue reste volontairement petit et la note
        // vit dans le JSON `specifications`, pas dans une colonne indexée.
        $sommelier = Product::published()
            ->with(['brand', 'variants', 'images'])
            ->get()
            ->filter(fn (Product $p) => (int) (($p->specifications ?? [])['note'] ?? 0) >= 92)
            ->sortByDesc(fn (Product $p) => (int) (($p->specifications ?? [])['note'] ?? 0))
            ->take(4)
            ->values();

        return view('catalog::home', compact('featured', 'categories', 'sommelier'));
    }
}
