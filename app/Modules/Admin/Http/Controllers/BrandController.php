<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Catalog\Domain\Models\Brand;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::query()->withCount('products')->orderBy('name')->paginate(30);

        return view('admin::brands.index', compact('brands'));
    }

    public function create(): View
    {
        return view('admin::brands.form', ['brand' => new Brand]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        Brand::create($data);

        return redirect()->route('admin.brands.index')->with('success', 'Marque créée.');
    }

    public function edit(Brand $brand): View
    {
        return view('admin::brands.form', compact('brand'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $this->validated($request);
        $brand->update($data);

        return redirect()->route('admin.brands.index')->with('success', 'Marque mise à jour.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $n = 2;
        while (Brand::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
