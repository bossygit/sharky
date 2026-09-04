<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a3d62">

    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', 'Boutique en ligne nasande.cg — cosmétique, parapharmacie et soin au Congo-Brazzaville. Livraison à Brazzaville.')">
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large')">

    <link rel="canonical" href="@yield('canonical', url()->current())">

    {{-- OpenGraph --}}
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', 'Boutique nasande.cg')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif
    <meta property="og:locale" content="fr_CG">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', config('app.name'))">
    <meta name="twitter:description" content="@yield('og_description', 'Boutique nasande.cg')">
    @hasSection('og_image')
        <meta name="twitter:image" content="@yield('og_image')">
    @endif

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    {{-- Préchargement police système : on évite Google Fonts pour la perf --}}

    <style>
        :root {
            --c-bg: #ffffff;
            --c-text: #0a0a0a;
            --c-muted: #6b7280;
            --c-primary: #0a3d62;
            --c-primary-contrast: #ffffff;
            --c-accent: #d4a96a;
            --c-border: #e5e7eb;
            --c-success: #16a34a;
            --c-danger: #dc2626;
            --r-sm: 4px;
            --r-md: 8px;
            --r-lg: 12px;
            --shadow-sm: 0 1px 2px rgba(0,0,0,.06);
            --shadow-md: 0 4px 12px rgba(0,0,0,.08);
            --container: 1200px;
        }
        * { box-sizing: border-box; }
        html { font-size: 16px; -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--c-text);
            background: var(--c-bg);
            line-height: 1.55;
        }
        a { color: var(--c-primary); text-decoration: none; }
        a:hover, a:focus-visible { text-decoration: underline; }
        :focus-visible { outline: 3px solid var(--c-accent); outline-offset: 2px; border-radius: var(--r-sm); }
        img { max-width: 100%; height: auto; display: block; }
        h1, h2, h3, h4 { margin: 0 0 .5em; line-height: 1.25; }
        h1 { font-size: 1.875rem; }
        h2 { font-size: 1.5rem; }
        h3 { font-size: 1.25rem; }
        .container { max-width: var(--container); margin: 0 auto; padding: 0 1rem; }
        .skip-link { position: absolute; left: -10000px; top: auto; }
        .skip-link:focus { left: 1rem; top: 1rem; background: #fff; padding: .5rem 1rem; z-index: 100; box-shadow: var(--shadow-md); }

        /* Header */
        .site-header { border-bottom: 1px solid var(--c-border); background-color: #ffffff; position: sticky; top: 0; z-index: 50; box-shadow: 0 2px 8px rgba(0,0,0,.08), 0 1px 3px rgba(0,0,0,.06); }
        .site-header .bar { display: flex; align-items: center; justify-content: space-between; padding: .75rem 0; gap: 1rem; }
        .brand { font-weight: 700; font-size: 1.25rem; color: var(--c-primary); }
        .main-nav { display: flex; gap: 1rem; flex-wrap: wrap; }
        .main-nav a { color: var(--c-text); }

        /* Cards */
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.25rem; }
        .product-card { border: 1px solid var(--c-border); border-radius: var(--r-md); overflow: hidden; background: #fff; transition: box-shadow .15s; display: flex; flex-direction: column; }
        .product-card:hover { box-shadow: var(--shadow-md); }
        .product-card .thumb { aspect-ratio: 1/1; background: #f8f9fa; display: flex; align-items: center; justify-content: center; }
        .product-card .thumb img { width: 100%; height: 100%; object-fit: contain; padding: .5rem; }
        .product-card .body { padding: .75rem 1rem 1rem; display: flex; flex-direction: column; gap: .25rem; flex: 1; }
        .product-card .brand-name { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: var(--c-muted); }
        .product-card .title { font-size: .95rem; font-weight: 600; color: var(--c-text); margin: 0; }
        .product-card .price { margin-top: auto; font-weight: 700; color: var(--c-primary); padding-top: .5rem; }

        /* Product page */
        .product-detail { display: grid; grid-template-columns: 1fr; gap: 2rem; padding: 2rem 0; }
        @media (min-width: 768px) { .product-detail { grid-template-columns: 1fr 1fr; } }
        .product-gallery img { width: 100%; border: 1px solid var(--c-border); border-radius: var(--r-md); background: #fafafa; }
        .product-meta .price-block { font-size: 1.75rem; color: var(--c-primary); font-weight: 700; margin: 1rem 0; }
        .badge { display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .8rem; background: #ecfdf5; color: var(--c-success); }
        .badge.out { background: #fef2f2; color: var(--c-danger); }
        .breadcrumb { font-size: .85rem; color: var(--c-muted); padding: 1rem 0 0; }
        .breadcrumb ol { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: .25rem; }
        .breadcrumb li:not(:last-child)::after { content: " ›"; color: var(--c-muted); margin-left: .25rem; }

        /* Footer */
        .site-footer { border-top: 1px solid var(--c-border); margin-top: 4rem; padding: 2rem 0; color: var(--c-muted); font-size: .9rem; background: #fafafa; }

        /* Pagination */
        .pagination { display: flex; gap: .25rem; justify-content: center; padding: 2rem 0; flex-wrap: wrap; }
        .pagination .page-item a, .pagination .page-item span { display: inline-block; padding: .5rem .75rem; border: 1px solid var(--c-border); border-radius: var(--r-sm); }
        .pagination .page-item.active span { background: var(--c-primary); color: #fff; border-color: var(--c-primary); }
        .pagination .page-item.disabled span { opacity: .5; }
        .sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); border:0; }
        .search-wrap { flex:1; max-width:360px; position:relative; }
        .search-wrap input { width:100%; padding:.45rem .65rem; border:1px solid var(--c-border); border-radius:6px; }
        #search-suggest { position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid var(--c-border); border-radius:6px; box-shadow:var(--shadow-md); z-index:60; padding:.5rem .75rem; }
    </style>

    <script src="https://unpkg.com/htmx.org@2.0.4" defer></script>
    @stack('head')

    @if ($ga4Id = config('services.analytics.ga4_id'))
        <!-- Google Analytics 4 -->
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

    @include('catalog::partials.header')

    <main id="content" role="main">
        @yield('breadcrumb')
        @yield('content')
    </main>

    @include('catalog::partials.footer')

    @stack('jsonld')
    <script src="/js/nasande-track.js" defer></script>
    @stack('scripts')
</body>
</html>
