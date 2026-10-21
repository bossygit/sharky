@extends('catalog::layouts.app')

@section('title', ($query !== '' ? 'Recherche : '.$query : 'Recherche') . ' — Nasande Vins')
@section('robots', 'noindex, follow')

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li aria-current="page">Recherche</li>
                </ol>
            </nav>
            <h1>Recherche</h1>
            <form method="GET" action="{{ route('search') }}" role="search" style="margin-top:1.5rem; max-width:560px;">
                <label for="q" class="sr-only">Rechercher un vin, une maison, une région</label>
                <div style="display:flex; border-bottom:1px solid var(--ink);">
                    <input id="q" name="q" type="search" value="{{ $query }}" placeholder="Un vin, une maison, une région…"
                           style="flex:1; border:0; background:transparent; padding:.7rem 0; font-size:1rem; outline:none;" autocomplete="off">
                    <button type="submit" class="btn" style="padding:.7rem 1.4rem; border:0;">Chercher</button>
                </div>
            </form>
        </div>
    </section>

    <section class="container" style="padding:2rem 0 4rem;">
        @if ($query === '')
            <p class="muted">Saisissez un terme pour explorer la cave.</p>
            <p><a class="link-arrow" href="{{ route('wines.index') }}">Voir toute la cave</a></p>
        @elseif ($count === 0)
            <div class="empty-state">
                <h2>Aucun résultat pour « {{ $query }} »</h2>
                <p>Essayez un autre mot-clé, une région ou le nom d'une maison.</p>
                <p><a class="link-arrow" href="{{ route('wines.index') }}">Parcourir la cave</a></p>
            </div>
        @else
            <p class="page-hero-meta" style="color:var(--muted);">{{ $count }} résultat{{ $count > 1 ? 's' : '' }} pour « {{ $query }} »</p>
            <div class="wine-grid" style="margin-top:1.5rem;">
                @foreach ($products as $product)
                    @include('catalog::partials.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </section>
@endsection
