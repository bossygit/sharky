@extends('admin::layouts.app')

@section('title', 'Produits')

@section('content')
    <div class="actions" style="justify-content:space-between; margin-bottom:1rem;">
        <div>
            <h1 style="margin:0;">Produits</h1>
            <p class="muted">{{ $products->total() }} produit(s)</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.products.create') }}">+ Nouveau produit</a>
    </div>

    <div class="card">
        <form method="GET" class="actions">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher…" style="max-width:240px;">
            <select name="status">
                <option value="">Tous statuts</option>
                @foreach (['draft','published','archived'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
                @endforeach
            </select>
            <select name="origin">
                <option value="">Toutes origines</option>
                <option value="drupal_migration" @selected(request('origin')==='drupal_migration')>Drupal</option>
                <option value="backoffice" @selected(request('origin')==='backoffice')>Backoffice</option>
            </select>
            <button class="btn" type="submit">Filtrer</button>
        </form>
    </div>

    <div class="card">
        <table>
            <thead>
            <tr>
                <th>Titre</th>
                <th>Statut</th>
                <th>Origine</th>
                <th>Prix</th>
                <th>URL</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($products as $product)
                @php $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first(); @endphp
                <tr>
                    <td>
                        <strong>{{ $product->title }}</strong>
                        @if ($product->brand)<br><span class="muted">{{ $product->brand->name }}</span>@endif
                    </td>
                    <td><span class="badge badge-{{ $product->status }}">{{ $product->status }}</span></td>
                    <td>{{ $product->origin }}</td>
                    <td>{{ $variant?->formatted_price ?? '—' }}</td>
                    <td><code>{{ $product->canonical_path }}</code></td>
                    <td class="actions">
                        <a href="{{ route('admin.products.edit', $product) }}">Éditer</a>
                        @if ($product->status === 'published')
                            <a href="{{ $product->canonical_path }}" target="_blank" rel="noopener">Voir</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $products->links() }}</div>
    </div>
@endsection
