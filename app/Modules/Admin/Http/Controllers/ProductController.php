<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Application\ProductAdminService;
use App\Modules\Catalog\Domain\Models\Brand;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Media;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Enrichment\Application\EnrichmentOrchestrator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    public function __construct(private ProductAdminService $products) {}

    public function index(Request $request): View
    {
        $query = Product::query()
            ->with(['brand', 'variants'])
            ->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($origin = $request->string('origin')->toString()) {
            $query->where('origin', $origin);
        }
        if ($search = $request->string('q')->toString()) {
            $query->where('title', 'ilike', '%'.$search.'%');
        }

        $products = $query->paginate(20)->withQueryString();

        return view('admin::products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin::products.form', [
            'product' => new Product(['status' => 'draft', 'origin' => 'backoffice']),
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $product = $this->products->create($data, $request->user()?->id);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $file) {
                $this->products->attachImage($product, $file, $i);
            }
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Produit créé en brouillon.');
    }

    public function edit(Product $product): View
    {
        $product->load(['brand', 'categories', 'variants', 'images']);

        return view('admin::products.form', [
            'product' => $product,
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request);
        $this->products->update($product, $data);

        if ($request->hasFile('images')) {
            $start = $product->images()->count();
            foreach ($request->file('images') as $i => $file) {
                $this->products->attachImage($product, $file, $start + $i);
            }
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Produit mis à jour.');
    }

    public function publish(Product $product): RedirectResponse
    {
        $this->products->publish($product);

        return back()->with('success', 'Produit publié sur '.$product->canonical_path);
    }

    public function enrich(Product $product): RedirectResponse
    {
        try {
            app(EnrichmentOrchestrator::class)->enqueue($product);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Enrichissement IA lancé — brouillons disponibles sous peu.');
    }

    public function archive(Product $product): RedirectResponse
    {
        $this->products->archive($product);

        return back()->with('success', 'Produit archivé.');
    }

    public function destroyImage(Product $product, Media $media): RedirectResponse
    {
        if (! $product->images()->where('media.id', $media->id)->exists()) {
            abort(404);
        }
        $this->products->detachImage($product, $media);

        return back()->with('success', 'Image retirée.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'short_description_html' => ['nullable', 'string'],
            'description_html' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'featured' => ['nullable', 'boolean'],
            'sku' => ['nullable', 'string', 'max:64'],
            'variant_title' => ['nullable', 'string', 'max:255'],
            'price_minor' => ['required', 'integer', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:5120'],

            // Fiche vin (stockée dans le JSON `specifications`)
            'millesime' => ['nullable', 'string', 'max:12'],
            'region' => ['nullable', 'string', 'max:120'],
            'pays' => ['nullable', 'string', 'max:80'],
            'appellation' => ['nullable', 'string', 'max:120'],
            'cepage' => ['nullable', 'string', 'max:160'],
            'alcool' => ['nullable', 'string', 'max:24'],
            'format' => ['nullable', 'string', 'max:24'],
            'service' => ['nullable', 'string', 'max:60'],
            'garde' => ['nullable', 'string', 'max:120'],
            'notes_degustation' => ['nullable', 'string', 'max:4000'],
            'accords' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'integer', 'min:0', 'max:100'],
            'note_source' => ['nullable', 'string', 'max:60'],
        ]);
    }
}
