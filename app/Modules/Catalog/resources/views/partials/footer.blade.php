@php
    $contact = $contact ?? \App\Modules\Commerce\Application\StorefrontContact::current();
@endphp
<footer class="site-footer" role="contentinfo">
    <div class="container footer-grid">
        <div>
            <p class="footer-brand">nasande<span>.cg</span></p>
            <p>Parapharmacie et cosmétique à Brazzaville. Livraison dans la capitale.</p>
        </div>
        <div>
            <p class="footer-heading">Contact</p>
            <ul>
                @if ($contact->hasPhone())
                    <li><a href="{{ $contact->telHref() }}">{{ $contact->phone }}</a></li>
                @endif
                @if ($contact->hasWhatsapp())
                    <li><a href="{{ $contact->whatsappHref('Bonjour, je vous contacte depuis nasande.cg.') }}">WhatsApp</a></li>
                @endif
                @if ($contact->hasEmail())
                    <li><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></li>
                @endif
                <li><a href="{{ route('pages.contact') }}">Page contact</a></li>
            </ul>
        </div>
        <div>
            <p class="footer-heading">Informations</p>
            <ul>
                <li><a href="{{ route('pages.about') }}">À propos</a></li>
                <li><a href="{{ route('pages.terms') }}">Conditions générales</a></li>
                <li><a href="{{ route('pages.returns') }}">Retours</a></li>
            </ul>
        </div>
    </div>
    <div class="container footer-meta">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }} — Brazzaville, République du Congo.</p>
        <p>Paiement à la livraison. Airtel Money et MTN MoMo : instructions USSD au checkout.</p>
    </div>
</footer>
