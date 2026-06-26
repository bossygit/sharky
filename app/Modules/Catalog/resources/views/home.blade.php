@extends('catalog::layouts.app')

@section('title', config('app.name').' — Parapharmacie et cosmétique à Brazzaville')
@section('meta_description', 'Découvrez plus de 700 produits de parapharmacie, cosmétique et soin disponibles à Brazzaville. Livraison rapide. Paiement Airtel Money, MTN MoMo ou à la livraison.')

@section('content')
    <section class="container" style="padding: 2.5rem 0 1rem;">
        <h1 style="font-size: 2rem;">Votre parapharmacie et cosmétique en ligne au Congo</h1>
        <p style="color: var(--c-muted); max-width: 60ch;">
            Soins, beauté, hygiène, maman et bébé. Livraison à Brazzaville.
            Paiement Airtel Money, MTN MoMo ou à la livraison.
        </p>
    </section>

    @if ($featured->isNotEmpty())
        <section class="container" style="padding: 1rem 0 2rem;">
            <h2>Produits mis en avant</h2>
            <div class="product-grid">
                @foreach ($featured as $product)
                    @include('catalog::partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if ($categories->isNotEmpty())
        <section class="container" style="padding: 1rem 0 2rem;">
            <h2>Catégories</h2>
            <ul style="list-style: none; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .5rem;">
                @foreach ($categories as $c)
                    <li><a href="/product/{{ $c->slug }}" style="display: block; padding: .75rem 1rem; border: 1px solid var(--c-border); border-radius: var(--r-md);">{{ $c->name }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($latest->isNotEmpty())
        <section class="container" style="padding: 1rem 0 2rem;">
            <h2>Tous nos produits récents</h2>
            <div class="product-grid">
                @foreach ($latest as $product)
                    @include('catalog::partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if ($brands->isNotEmpty())
        <section class="container" style="padding: 1rem 0 3rem;">
            <h2>Marques</h2>
            <ul style="list-style: none; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: .5rem;">
                @foreach ($brands as $brand)
                    <li><a href="/brand/{{ $brand->slug }}" style="display: block; padding: .5rem; border: 1px solid var(--c-border); border-radius: var(--r-sm); text-align: center;">{{ $brand->name }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection

@push('jsonld')
    @php
        $orgJsonld = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('app.name'),
            'url' => url('/'),
            'logo' => url('/favicon.svg'),
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Brazzaville',
                'addressCountry' => 'CG',
            ],
            'areaServed' => ['@type' => 'City', 'name' => 'Brazzaville'],
        ];
        $websiteJsonld = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => url('/'),
            'inLanguage' => 'fr-CG',
        ];
    @endphp
    <script type="application/ld+json">
    {!! json_encode($orgJsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <script type="application/ld+json">
    {!! json_encode($websiteJsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
