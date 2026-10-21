@php
    $contact = $contact ?? \App\Modules\Commerce\Application\StorefrontContact::current();
    $footerCategories = \Illuminate\Support\Facades\Cache::remember(
        'vins.nav.categories.v1',
        now()->addMinutes(30),
        fn () => \App\Modules\Catalog\Domain\Models\Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'slug'])
    );
@endphp
<footer class="site-footer" role="contentinfo">
    <div class="container footer-grid">
        <div>
            <p class="footer-brand-name">Nasande</p>
            <p class="footer-brand-sub">Vins · Cave de Brazzaville</p>
            <p class="footer-baseline">
                Une cave en ligne pensée pour Brazzaville : champagnes, grands crus et vins de maisons
                choisies, livrés chez vous avec conseil.
            </p>
            <p class="footer-baseline" style="margin-top:1rem;">
                Brazzaville, République du Congo
            </p>
        </div>

        <div>
            <p class="footer-heading">La cave</p>
            <ul>
                @foreach ($footerCategories as $cat)
                    <li><a href="{{ route('wines.category', $cat->slug) }}">{{ $cat->name }}</a></li>
                @endforeach
                <li><a href="{{ route('wines.index') }}">Toute la sélection</a></li>
            </ul>
        </div>

        <div>
            <p class="footer-heading">La maison</p>
            <ul>
                <li><a href="{{ route('pages.about') }}">Notre histoire</a></li>
                <li><a href="{{ route('pages.gifts') }}">Cadeaux & coffrets</a></li>
                <li><a href="{{ route('pages.delivery') }}">Livraison & paiement</a></li>
                <li><a href="{{ route('pages.contact') }}">Contact</a></li>
                <li><a href="{{ route('pages.terms') }}">Conditions générales</a></li>
                <li><a href="{{ route('pages.returns') }}">Retours</a></li>
            </ul>
        </div>

        <div>
            <p class="footer-heading">Service client</p>
            <ul>
                @if ($contact->hasPhone())
                    <li><a href="{{ $contact->telHref() }}">{{ $contact->phone }}</a></li>
                @endif
                @if ($contact->hasWhatsapp())
                    <li><a href="{{ $contact->whatsappHref('Bonjour, je vous contacte depuis Nasande Vins.') }}">WhatsApp</a></li>
                @endif
                @if ($contact->hasEmail())
                    <li><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></li>
                @endif
                <li>Lun – Sam · 9h – 19h</li>
                <li>Livraison à {{ config('commerce.delivery.city', 'Brazzaville') }}</li>
            </ul>
        </div>
    </div>

    <div class="container footer-bottom">
        <div class="pay-list" aria-label="Moyens de paiement">
            <span>Airtel Money</span>
            <span>MTN MoMo</span>
            <span>À la livraison</span>
        </div>
        <div style="display:flex; align-items:center; gap:.8rem;">
            <span class="age-badge" aria-label="Vente interdite aux mineurs">18+</span>
            <span>&copy; {{ date('Y') }} Nasande Vins · Brazzaville</span>
        </div>
        <p class="drink-notice">
            L'abus d'alcool est dangereux pour la santé. À consommer avec modération.
            La vente d'alcool est interdite aux personnes de moins de 18 ans.
        </p>
    </div>
</footer>
