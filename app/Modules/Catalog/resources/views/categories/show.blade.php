@extends('catalog::layouts.app')

@section('title', ($category->meta_title ?: $category->name).' — Nasande Vins')
@section('meta_description', $category->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $category->description), 155) ?: 'Découvrez '.$category->name.' de la cave Nasande Vins, livrés à Brazzaville.')

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li><a href="{{ route('wines.index') }}">La cave</a></li>
                    <li aria-current="page">{{ $category->name }}</li>
                </ol>
            </nav>
            <h1>{{ $category->name }}</h1>
            @if ($category->description)
                <p class="lede">{{ strip_tags($category->description) }}</p>
            @endif
            <p class="page-hero-meta">{{ $products->total() }} référence{{ $products->total() > 1 ? 's' : '' }} · Livraison à {{ config('commerce.delivery.city', 'Brazzaville') }}</p>
        </div>
    </section>

    <section class="container" style="padding-top:1.25rem;">
        @include('catalog::partials.plp', ['products' => $products, 'categories' => $categories, 'activeCategory' => $category, 'sort' => $sort])
    </section>
@endsection
