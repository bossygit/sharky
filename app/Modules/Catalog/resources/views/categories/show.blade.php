@extends('catalog::layouts.app')

@section('title', ($category->meta_title ?: $category->name).' | '.config('app.name'))
@section('meta_description', $category->meta_description ?: 'Découvrez les produits de la catégorie '.$category->name.' sur nasande.cg.')

@section('breadcrumb')
    <nav class="container breadcrumb" aria-label="Fil d'Ariane">
        <ol>
            <li><a href="{{ route('home') }}">Accueil</a></li>
            <li aria-current="page">{{ $category->name }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    <section class="container" style="padding: 2rem 0;">
        <header style="margin-bottom: 2rem;">
            <h1>{{ $category->name }}</h1>
            @if ($category->description)
                <div style="color: var(--c-muted);">{!! $category->description !!}</div>
            @endif
            <p style="color: var(--c-muted); font-size: .9rem;">{{ $products->total() }} produit{{ $products->total() > 1 ? 's' : '' }}</p>
        </header>

        @if ($products->isEmpty())
            <p>Aucun produit dans cette catégorie pour le moment.</p>
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
