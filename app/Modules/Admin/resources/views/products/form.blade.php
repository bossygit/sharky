@extends('admin::layouts.app')

@php
    $isEdit = $product->exists;
    $variant = $isEdit ? ($product->variants->firstWhere('is_default', true) ?? $product->variants->first()) : null;
@endphp

@section('title', $isEdit ? 'Éditer produit' : 'Nouveau produit')

@section('content')
    <div class="actions" style="justify-content:space-between; margin-bottom:1rem;">
        <div>
            <h1 style="margin:0;">{{ $isEdit ? 'Éditer le produit' : 'Nouveau produit' }}</h1>
            @if ($isEdit)
                <p class="muted">
                    Statut : <span class="badge badge-{{ $product->status }}">{{ $product->status }}</span>
                    — URL : <code>{{ $product->canonical_path }}</code>
                </p>
            @endif
        </div>
        <a class="btn" href="{{ route('admin.products.index') }}">← Retour</a>
    </div>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="card">
            <h2>Informations</h2>
            <label for="title">Titre *</label>
            <input id="title" name="title" value="{{ old('title', $product->title) }}" required>
            @error('title')<div class="error">{{ $message }}</div>@enderror

            <div class="row">
                <div>
                    <label for="brand_id">Marque</label>
                    <select id="brand_id" name="brand_id">
                        <option value="">— Aucune —</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id)==$brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="category_ids">Catégories</label>
                    <select id="category_ids" name="category_ids[]" multiple size="6">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(collect(old('category_ids', $product->categories?->pluck('id')))->contains($cat->id))>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <label for="short_description_html">Description courte (HTML)</label>
            <textarea id="short_description_html" name="short_description_html">{{ old('short_description_html', $product->short_description_html) }}</textarea>

            <label for="description_html">Description longue (HTML)</label>
            <textarea id="description_html" name="description_html" style="min-height:200px;">{{ old('description_html', $product->description_html) }}</textarea>

            <label><input type="checkbox" name="featured" value="1" @checked(old('featured', $product->featured))> Produit mis en avant</label>
        </div>

        <div class="card">
            <h2>Prix & stock</h2>
            <div class="row">
                <div>
                    <label for="price_minor">Prix (XAF) *</label>
                    <input id="price_minor" type="number" name="price_minor" min="0" required
                           value="{{ old('price_minor', $variant?->price_minor ?? 0) }}">
                </div>
                <div>
                    <label for="stock">Stock</label>
                    <input id="stock" type="number" name="stock" min="0"
                           value="{{ old('stock', $variant?->stock) }}" placeholder="Vide = non suivi">
                </div>
            </div>
            <div class="row">
                <div>
                    <label for="sku">SKU</label>
                    <input id="sku" name="sku" value="{{ old('sku', $variant?->sku) }}">
                </div>
                <div>
                    <label for="variant_title">Titre variation</label>
                    <input id="variant_title" name="variant_title" value="{{ old('variant_title', $variant?->title) }}">
                </div>
            </div>
        </div>

        <div class="card">
            <h2>SEO</h2>
            <label for="meta_title">Meta title</label>
            <input id="meta_title" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}">
            <label for="meta_description">Meta description</label>
            <textarea id="meta_description" name="meta_description" style="min-height:80px;">{{ old('meta_description', $product->meta_description) }}</textarea>
        </div>

        <div class="card">
            <h2>Images</h2>
            @if ($isEdit && $product->images->isNotEmpty())
                <div class="img-grid">
                    @foreach ($product->images as $img)
                        <div>
                            <img src="{{ $img->url }}" alt="{{ $img->alt_text }}">
                        </div>
                    @endforeach
                </div>
                <div class="actions" style="margin-top:.75rem;">
                    @foreach ($product->images as $img)
                        <form method="POST" action="{{ route('admin.products.images.destroy', [$product, $img]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger" type="submit" style="font-size:.75rem;">Retirer #{{ $loop->iteration }}</button>
                        </form>
                    @endforeach
                </div>
            @endif
            <label for="images" style="margin-top:1rem;">Ajouter des images</label>
            <input id="images" type="file" name="images[]" accept="image/*" multiple>
            @error('images.*')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Enregistrer' : 'Créer en brouillon' }}</button>
            @if ($isEdit && $product->status === 'published')
                <a class="btn" href="{{ $product->canonical_path }}" target="_blank" rel="noopener">Voir en ligne</a>
            @endif
        </div>
    </form>

    @if ($isEdit)
        <div class="actions" style="margin-top:1rem;">
            <form method="POST" action="{{ route('admin.products.enrich', $product) }}">
                @csrf
                <button class="btn btn-primary" type="submit">Enrichir avec IA</button>
            </form>
            @if ($product->ai_enrichment_status === 'review')
                <a class="btn" href="{{ route('admin.enrichment.show', $product) }}">Revoir brouillon IA</a>
            @endif
            @if ($product->status !== 'published')
                <form method="POST" action="{{ route('admin.products.publish', $product) }}">
                    @csrf
                    <button class="btn btn-ok" type="submit">Publier</button>
                </form>
            @endif
            @if ($product->status === 'published')
                <form method="POST" action="{{ route('admin.products.archive', $product) }}">
                    @csrf
                    <button class="btn btn-danger" type="submit">Archiver</button>
                </form>
            @endif
        </div>
    @endif
@endsection
