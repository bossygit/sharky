@extends('catalog::layouts.app')

@section('title', 'Toute la cave — Nasande Vins')
@section('meta_description', 'Tous les vins et champagnes de la cave Nasande : rouges, blancs, rosés, effervescents et vins doux, livrés à Brazzaville.')

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li aria-current="page">La cave</li>
                </ol>
            </nav>
            <h1>La cave</h1>
            <p class="lede">
                Une sélection resserrée de champagnes, grands crus et vins de maisons choisies.
                Chaque bouteille est goûtée ou validée par la maison avant d'entrer dans la cave.
            </p>
            <p class="page-hero-meta">{{ $products->total() }} référence{{ $products->total() > 1 ? 's' : '' }} · Livraison à {{ config('commerce.delivery.city', 'Brazzaville') }}</p>
        </div>
    </section>

    <section class="container" style="padding-top:1.25rem;">
        @include('catalog::partials.plp', ['products' => $products, 'categories' => $categories, 'activeCategory' => $activeCategory, 'sort' => $sort])
    </section>
@endsection
