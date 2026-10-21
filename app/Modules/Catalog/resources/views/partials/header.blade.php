@php
    $contact = $contact ?? \App\Modules\Commerce\Application\StorefrontContact::current();
    $cartCount = app(\App\Modules\Commerce\Application\CartService::class)->count();
    $navCategories = \Illuminate\Support\Facades\Cache::remember(
        'vins.nav.categories.v1',
        now()->addMinutes(30),
        fn () => \App\Modules\Catalog\Domain\Models\Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'slug'])
    );
@endphp
<header class="site-header" role="banner">
    <div class="container header-top">
        <div class="header-meta">
            <span>Brazzaville, Congo</span>
            <span class="sep" aria-hidden="true"></span>
            @if ($contact->hasPhone())
                <a href="{{ $contact->telHref() }}">{{ $contact->phone }}</a>
            @else
                <span>Lun – Sam · 9h – 19h</span>
            @endif
        </div>

        <a href="{{ route('home') }}" class="brandmark" aria-label="Nasande Vins — accueil">
            <span class="brandmark-main">Nasande</span>
            <span class="brandmark-sub">Vins · Cave de Brazzaville</span>
        </a>

        <div class="header-actions">
            <div class="search-wrap">
                <form class="search-form" method="GET" action="{{ route('search') }}" role="search">
                    <label for="header-search" class="sr-only">Rechercher un vin, une maison</label>
                    <input id="header-search" name="q" type="search" placeholder="Un vin, une maison…" autocomplete="off"
                           value="{{ request()->routeIs('search') ? request('q') : '' }}"
                           data-suggest-url="{{ route('search.suggest') }}">
                    <button type="submit" aria-label="Rechercher">⌕</button>
                </form>
                <div id="search-suggest" aria-live="polite"></div>
            </div>
            <a class="cart-link" href="{{ route('cart.show') }}">
                Panier
                @if ($cartCount > 0)
                    <span class="cart-count">{{ $cartCount }}</span>
                @endif
            </a>
        </div>
    </div>

    <div class="header-nav">
        <div class="container nav-row">
            <details class="mobile-menu">
                <summary>Menu</summary>
                <nav class="menu-panel" aria-label="Navigation mobile">
                    <a href="{{ route('wines.index') }}">Toute la cave</a>
                    @foreach ($navCategories as $cat)
                        <a href="{{ route('wines.category', $cat->slug) }}">{{ $cat->name }}</a>
                    @endforeach
                    <a href="{{ route('pages.gifts') }}">Cadeaux & coffrets</a>
                    <a href="{{ route('pages.about') }}">Notre histoire</a>
                    <a href="{{ route('pages.delivery') }}">Livraison & paiement</a>
                    <a href="{{ route('pages.contact') }}">Contact</a>
                    <p class="menu-contact">
                        @if ($contact->hasWhatsapp())
                            <a href="{{ $contact->whatsappHref('Bonjour, je vous contacte depuis Nasande Vins.') }}" style="border:0; padding:0; text-transform:none; letter-spacing:0;">WhatsApp</a> ·
                        @endif
                        @if ($contact->hasPhone())
                            <a href="{{ $contact->telHref() }}" style="border:0; padding:0; text-transform:none; letter-spacing:0;">{{ $contact->phone }}</a>
                        @endif
                    </p>
                </nav>
            </details>

            <nav aria-label="Navigation principale">
                <ul class="nav-list">
                    <li><a href="{{ route('wines.index') }}" @class(['is-active' => request()->routeIs('wines.index')])>Toute la cave</a></li>
                    @foreach ($navCategories as $cat)
                        <li><a href="{{ route('wines.category', $cat->slug) }}" @class(['is-active' => request()->is('vins/'.$cat->slug)])>{{ $cat->name }}</a></li>
                    @endforeach
                    <li class="nav-gold"><a href="{{ route('pages.gifts') }}" @class(['is-active' => request()->routeIs('pages.gifts')])>Cadeaux</a></li>
                    <li><a href="{{ route('pages.about') }}" @class(['is-active' => request()->routeIs('pages.about')])>Notre histoire</a></li>
                    <li><a href="{{ route('pages.contact') }}" @class(['is-active' => request()->routeIs('pages.contact')])>Contact</a></li>
                </ul>
            </nav>

            @if ($contact->hasPhone())
                <a class="nav-phone" href="{{ $contact->telHref() }}">Conseil · {{ $contact->phone }}</a>
            @endif
        </div>
    </div>
</header>

@push('scripts')
    <script>
        (function () {
            var input = document.getElementById('header-search');
            var box = document.getElementById('search-suggest');
            if (!input || !box) return;

            var timer = null;
            var url = input.getAttribute('data-suggest-url');

            input.addEventListener('input', function () {
                var q = input.value.trim();
                clearTimeout(timer);
                if (q.length < 2) {
                    box.innerHTML = '';
                    return;
                }
                timer = setTimeout(function () {
                    fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.ok ? r.text() : ''; })
                        .then(function (html) { box.innerHTML = html; })
                        .catch(function () { box.innerHTML = ''; });
                }, 180);
            });

            document.addEventListener('click', function (event) {
                if (event.target !== input && !box.contains(event.target)) {
                    box.innerHTML = '';
                }
            });
        })();
    </script>
@endpush
