@extends('catalog::layouts.app')

@section('title', ($brand->meta_title ?: $brand->name).' | '.config('app.name'))
@section('meta_description', $brand->meta_description ?: 'Tous les produits '.$brand->name.' disponibles sur nasande.cg.')

@section('breadcrumb')
    <nav class="container breadcrumb" aria-label="Fil d'Ariane">
        <ol>
            <li><a href="{{ route('home') }}">Accueil</a></li>
            <li aria-current="page">{{ $brand->name }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    <section class="container" style="padding: 2rem 0;">
        <header style="margin-bottom: 2rem;">
            <h1>{{ $brand->name }}</h1>
            @if ($brand->description)
                <div style="color: var(--c-muted);">{!! $brand->description !!}</div>
            @endif
            <p style="color: var(--c-muted); font-size: .9rem;">{{ $products->total() }} produit{{ $products->total() > 1 ? 's' : '' }}</p>
        </header>

        @if ($products->isEmpty())
            <p>Aucun produit pour cette marque.</p>
        @else
            <div class="product-grid">
                @foreach ($products as $product)
                    @include('catalog::partials.product-card', ['product' => $product])
                @endforeach
            </div>

            {{ $products->onEachSide(1)->links() }}
        @endif
    </section>
@endsection
