<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#171310">

    <title>@yield('title', 'Nasande Vins — La cave en ligne de Brazzaville')</title>
    <meta name="description" content="@yield('meta_description', 'Champagnes, grands crus et vins de maisons choisies, livrés à Brazzaville. Paiement Airtel Money, MTN MoMo ou à la livraison.')">
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large')">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <meta property="og:site_name" content="Nasande Vins">
    <meta property="og:locale" content="fr_CG">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', 'Nasande Vins — La cave en ligne de Brazzaville')">
    <meta property="og:description" content="@yield('og_description', 'Champagnes, grands crus et vins de maisons choisies, livrés à Brazzaville.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/vins/hero.svg'))">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'Nasande Vins')">
    <meta name="twitter:description" content="@yield('og_description', 'La cave en ligne de Brazzaville.')">

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/vins/favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/vins.css') }}?v=1">

    @stack('head')

    @if ($ga4Id = config('services.analytics.ga4_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', '{{ $ga4Id }}');
        </script>
    @endif
</head>
<body>
<a class="skip-link" href="#content">Aller au contenu</a>

<div class="announce">
    Livraison à {{ config('commerce.delivery.city', 'Brazzaville') }} — Airtel Money, MTN MoMo ou paiement à la livraison
</div>

@include('catalog::partials.header')

<main id="content" role="main">
    @yield('content')
</main>

@include('catalog::partials.footer')

@stack('jsonld')
@stack('scripts')
</body>
</html>
