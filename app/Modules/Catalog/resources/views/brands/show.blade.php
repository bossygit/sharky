@extends('catalog::layouts.app')

@section('title', ($brand->meta_title ?: $brand->name).' — Nasande Vins')
@section('meta_description', $brand->meta_description ?: 'Tous les vins de la maison '.$brand->name.' disponibles à la cave Nasande Vins, livrés à Brazzaville.')

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li><a href="{{ route('wines.index') }}">La cave</a></li>
                    <li aria-current="page">{{ $brand->name }}</li>
                </ol>
            </nav>
            <h1>{{ $brand->name }}</h1>
            @if ($brand->description)
                <p class="lede">{{ strip_tags($brand->description) }}</p>
            @endif
            <p class="page-hero-meta">{{ $products->total() }} référence{{ $products->total() > 1 ? 's' : '' }}</p>
        </div>
    </section>

    <section class="container" style="padding:2rem 0 4rem;">
        @if ($products->isEmpty())
            <div class="empty-state">
                <h2>Aucune bouteille de cette maison pour le moment</h2>
                <p>Revenez bientôt, la cave s'enrichit chaque mois.</p>
            </div>
        @else
            <div class="wine-grid">
                @foreach ($products as $product)
                    @include('catalog::partials.product-card', ['product' => $product])
                @endforeach
            </div>
            <div class="plp-foot">
                {{ $products->links('catalog::partials.pagination') }}
            </div>
        @endif
    </section>
@endsection
