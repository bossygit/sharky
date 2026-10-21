@extends('catalog::layouts.app')

@php
    $contact = \App\Modules\Commerce\Application\StorefrontContact::current();
@endphp

@section('title', 'Nasande Vins — La cave en ligne de Brazzaville')
@section('meta_description', 'Champagnes, grands crus et vins de maisons choisies, livrés à Brazzaville. Conseil personnalisé, paiement Airtel Money, MTN MoMo ou à la livraison.')
@section('og_title', 'Nasande Vins — La cave en ligne de Brazzaville')
@section('og_description', 'Champagnes, grands crus et vins de maisons choisies, livrés à Brazzaville.')
@section('og_image', asset('images/vins/hero.svg'))

@section('content')
    <section class="hero">
        <div class="hero-media">
            <img src="{{ asset('images/vins/hero.svg') }}" alt="" width="1800" height="1100" fetchpriority="high">
        </div>
        <div class="container hero-inner">
            <div class="hero-copy">
                <p class="eyebrow">La cave en ligne de Brazzaville</p>
                <h1 class="hero-title">Le vin, comme un <em>art de vivre</em>.</h1>
                <p class="hero-sub">
                    Champagnes, grands crus et belles bouteilles choisies une à une.
                    Livrées chez vous à Brazzaville, avec le conseil qui va avec.
                </p>
                <div class="hero-actions">
                    <a class="btn btn-gold" href="{{ route('wines.index') }}">Découvrir la cave</a>
                    <a class="link-arrow" href="{{ route('wines.category', 'champagnes') }}" style="color:var(--cream); border-color:var(--gold-soft);">Nos champagnes</a>
                </div>
            </div>
        </div>
        <p class="hero-note">Livraison à {{ config('commerce.delivery.city', 'Brazzaville') }} · Conseil personnalisé</p>
    </section>

    <div class="usp">
        <div class="container usp-grid">
            <div class="usp-item"><strong>Livraison</strong> <span>à {{ config('commerce.delivery.city', 'Brazzaville') }}, sous 24 h</span></div>
            <div class="usp-item"><strong>Paiement</strong> <span>Airtel Money · MTN MoMo · à la livraison</span></div>
            <div class="usp-item"><strong>Conseil</strong> <span>un sommelier vous répond sur WhatsApp</span></div>
        </div>
    </div>

    @if ($featured->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Les incontournables</p>
                        <h2 class="section-title">Ce que l'on sert le plus</h2>
                        <p class="section-lede">Les bouteilles qui reviennent sur les tables de Brazzaville : valeurs sûres, grandes maisons et plaisirs immédiats.</p>
                    </div>
                    <a class="link-arrow" href="{{ route('wines.index') }}">Voir toute la cave</a>
                </div>
                <div class="rail">
                    @foreach ($featured as $product)
                        @include('catalog::partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($categories->isNotEmpty())
        <section class="section section-cream">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Explorer</p>
                        <h2 class="section-title">Par envie, par région, par style</h2>
                    </div>
                    <a class="link-arrow" href="{{ route('wines.index') }}">Toute la sélection</a>
                </div>
                <div class="cat-grid">
                    @foreach ($categories as $cat)
                        <a class="cat-tile" href="{{ route('wines.category', $cat->slug) }}">
                            <img src="{{ asset('images/vins/cat-'.$cat->slug.'.svg') }}" alt="" loading="lazy" width="900" height="1100">
                            <span class="cat-tile-body">
                                <span class="cat-tile-name">{{ $cat->name }}</span>
                                <span class="cat-tile-count">{{ $cat->published_products_count }} référence{{ $cat->published_products_count > 1 ? 's' : '' }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section">
        <div class="container">
            <div class="editorial-grid">
                <a class="editorial-card" href="{{ route('wines.category', 'vins-rouges') }}">
                    <img src="{{ asset('images/vins/editorial-bordeaux.svg') }}" alt="" loading="lazy" width="900" height="1100">
                    <span class="editorial-copy">
                        <p class="eyebrow">Bordeaux</p>
                        <h3>L'élégance intemporelle</h3>
                        <p>Des crus de garde aux bordeaux de plaisir : la rive gauche, la rive droite et les grands assemblages.</p>
                        <span class="link-arrow">Explorer les rouges</span>
                    </span>
                </a>
                <a class="editorial-card" href="{{ route('wines.category', 'champagnes') }}">
                    <img src="{{ asset('images/vins/editorial-champagne.svg') }}" alt="" loading="lazy" width="900" height="1100">
                    <span class="editorial-copy">
                        <p class="eyebrow">Champagne</p>
                        <h3>Une signature, une occasion</h3>
                        <p>Maisons de légende et bulles de caractère, pour marquer les grands moments — et les autres.</p>
                        <span class="link-arrow">Explorer les champagnes</span>
                    </span>
                </a>
            </div>
        </div>
    </section>

    @if ($sommelier->isNotEmpty())
        <section class="section section-cream">
            <div class="container sommelier">
                <div class="sommelier-copy">
                    <p class="eyebrow">La sélection du sommelier</p>
                    <h2 class="section-title">Des bouteilles qui méritent le détour</h2>
                    <p>Notées par les critiques, choisies pour leur équilibre et leur histoire. Une short-list pour les dîners qui comptent.</p>
                    <a class="link-arrow" href="{{ route('wines.index', ['tri' => 'note']) }}">Toute la sélection</a>
                </div>
                <div class="sommelier-grid">
                    @foreach ($sommelier as $product)
                        @include('catalog::partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="gift">
        <div class="gift-grid">
            <div class="gift-copy">
                <p class="eyebrow">Cadeaux & coffrets</p>
                <h2 class="section-title">Offrir une bouteille, offrir un moment.</h2>
                <p>
                    Coffrets bois, étuis, cartes manuscrites : nous composons le cadeau avec vous,
                    pour un anniversaire, un départ ou un dîner d'affaires.
                </p>
                <div class="hero-actions" style="margin-top:1.75rem;">
                    <a class="btn btn-gold" href="{{ route('pages.gifts') }}">Composer un coffret</a>
                    @if ($contact->hasWhatsapp())
                        <a class="link-arrow" href="{{ $contact->whatsappHref('Bonjour, je souhaite composer un coffret cadeau Nasande Vins.') }}" style="color:var(--cream); border-color:var(--gold-soft);">Parler à la maison</a>
                    @endif
                </div>
            </div>
            <div class="gift-media">
                <img src="{{ asset('images/vins/cadeaux.svg') }}" alt="" loading="lazy" width="1400" height="800">
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container team">
            <p class="eyebrow">La maison</p>
            <h2 class="section-title">Un doute sur un accord ?</h2>
            <p>
                Un poisson braisé, un poulet moambe, un anniversaire à préparer : dites-nous ce que vous cuisinez,
                nous vous proposons la bouteille. Le conseil fait partie du service.
            </p>
            <div class="team-actions">
                @if ($contact->hasWhatsapp())
                    <a class="btn" href="{{ $contact->whatsappHref('Bonjour, j\'aimerais un conseil pour choisir un vin.') }}">Écrire sur WhatsApp</a>
                @endif
                @if ($contact->hasPhone())
                    <a class="btn btn-outline-dark" href="{{ $contact->telHref() }}">Appeler la cave</a>
                @endif
                @unless ($contact->hasWhatsapp() || $contact->hasPhone())
                    <a class="btn" href="{{ route('pages.contact') }}">Nous contacter</a>
                @endunless
            </div>
            <p class="team-hours">Lundi – samedi · 9h – 19h · Brazzaville</p>
        </div>
    </section>

    <section class="quote-band">
        <div class="container">
            <blockquote>« Le bon vin ne s'improvise pas. Il se choisit, se raconte et se partage. »</blockquote>
            <cite>Maison Nasande</cite>
        </div>
    </section>
@endsection

@push('jsonld')
    @php
        $orgJsonld = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Nasande Vins',
            'url' => url('/'),
            'logo' => asset('images/vins/favicon.svg'),
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
            'name' => 'Nasande Vins',
            'url' => url('/'),
            'inLanguage' => 'fr-CG',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/recherche').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    @endphp
    <script type="application/ld+json">
    {!! json_encode($orgJsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode($websiteJsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
