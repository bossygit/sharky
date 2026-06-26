<header class="site-header" role="banner">
    <div class="container bar">
        <a href="{{ route('home') }}" class="brand" aria-label="Accueil {{ config('app.name') }}">
            nasande<span style="color: var(--c-accent);">.cg</span>
        </a>

        <div class="search-wrap" role="search">
            <form method="GET" action="{{ route('search') }}">
                <label for="header-search" class="sr-only">Rechercher</label>
                <input id="header-search" name="q" type="search" placeholder="Rechercher…" autocomplete="off"
                       hx-get="{{ route('search.suggest') }}"
                       hx-trigger="keyup changed delay:150ms, search"
                       hx-target="#search-suggest"
                       hx-swap="innerHTML">
            </form>
            <div id="search-suggest" aria-live="polite"></div>
        </div>

        <nav class="main-nav" aria-label="Navigation principale">
            <a href="{{ route('home') }}">Accueil</a>
            @php($cartCount = app(\App\Modules\Commerce\Application\CartService::class)->count())
            <a href="{{ route('cart.show') }}">
                Panier
                @if ($cartCount > 0)
                    ({{ $cartCount }})
                @endif
            </a>
        </nav>
    </div>
</header>
