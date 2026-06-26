<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Catalog\Domain\Models\Brand;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'products_total' => Product::count(),
            'products_published' => Product::where('status', 'published')->count(),
            'products_draft' => Product::where('status', 'draft')->count(),
            'products_backoffice' => Product::where('origin', 'backoffice')->count(),
            'products_no_image' => Product::where('status', 'published')
                ->whereDoesntHave('images')
                ->count(),
            'products_no_description' => Product::where('status', 'published')
                ->where(function ($q) {
                    $q->whereNull('description_html')
                        ->orWhere('description_html', '');
                })
                ->count(),
            'brands' => Brand::count(),
            'categories' => Category::count(),
        ];

        $recentDrafts = Product::query()
            ->where('origin', 'backoffice')
            ->where('status', 'draft')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin::dashboard', compact('stats', 'recentDrafts'));
    }
}
