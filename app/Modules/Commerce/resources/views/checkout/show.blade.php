@extends('catalog::layouts.app')

@section('title', 'Finaliser la commande — Nasande Vins')
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li><a href="{{ route('cart.show') }}">Panier</a></li>
                    <li aria-current="page">Commande</li>
                </ol>
            </nav>
            <h1>Finaliser la commande</h1>
            <p class="lede">Livraison à {{ $deliveryCity }}. Pas de compte à créer : vos coordonnées suffisent.</p>
        </div>
    </section>

    <div class="container">
        @if (session('error'))
            <div class="alert alert-error" style="margin-top:1.5rem;">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success" style="margin-top:1.5rem;">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error" style="margin-top:1.5rem;">
                <strong>Vérifiez les champs suivants :</strong>
                <ul style="margin:.4rem 0 0; list-style:disc; padding-left:1.1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="checkout-grid">
            <form method="POST" action="{{ route('checkout.store') }}" id="checkout-form">
                @csrf

                <div class="form-block">
                    <h2>Vos coordonnées</h2>
                    <div class="field">
                        <label for="customer_name">Nom complet *</label>
                        <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required autocomplete="name">
                        @error('customer_name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="customer_email">Email *</label>
                            <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}" required autocomplete="email">
                            @error('customer_email')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label for="customer_phone">Téléphone *</label>
                            <input id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" required placeholder="+242 06 XXX XX XX" autocomplete="tel">
                            @error('customer_phone')<div class="error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-block">
                    <h2>Livraison</h2>
                    <div class="field">
                        <label for="delivery_address_text">Adresse à {{ $deliveryCity }} *</label>
                        <textarea id="delivery_address_text" name="delivery_address_text" rows="3" required placeholder="Quartier, rue, repère, numéro de parcelle">{{ old('delivery_address_text') }}</textarea>
                        @error('delivery_address_text')<div class="error">{{ $message }}</div>@enderror
                        <p class="form-help">Un repère précis et un numéro joignable nous aident à livrer vite.</p>
                    </div>
                </div>

                <div class="form-block">
                    <h2>Paiement</h2>
                    <div class="pay-options">
                        @foreach ($paymentMethods as $method)
                            <label class="pay-option">
                                <input type="radio" name="payment_method" value="{{ $method->code() }}" @checked(old('payment_method', 'cash_on_delivery') === $method->code()) required>
                                <span>{{ $method->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_method')<div class="error">{{ $message }}</div>@enderror
                    <p class="form-help" style="margin-top:.75rem;">
                        Les instructions Mobile Money s'affichent après validation de la commande.
                        Le paiement à la livraison se règle en espèces à la remise.
                    </p>
                </div>

                <div class="form-block">
                    <h2>Une précision ?</h2>
                    <div class="field" style="margin-bottom:0;">
                        <label for="customer_notes">Notes (optionnel)</label>
                        <textarea id="customer_notes" name="customer_notes" rows="2" placeholder="Heure de livraison préférée, message cadeau…">{{ old('customer_notes') }}</textarea>
                    </div>
                </div>
            </form>

            <aside class="summary" aria-label="Récapitulatif">
                <h2>Votre commande</h2>
                <div class="summary-row"><span>Sous-total</span><span>{{ number_format($subtotal, 0, ',', ' ') }} FCFA</span></div>
                <div class="summary-row"><span>Livraison ({{ $deliveryCity }})</span><span>{{ number_format($shipping, 0, ',', ' ') }} FCFA</span></div>
                <div class="summary-row total"><span>Total</span><span>{{ number_format($total, 0, ',', ' ') }} FCFA</span></div>

                <button class="btn btn-block" type="submit" form="checkout-form">Confirmer la commande</button>

                <p class="summary-note">
                    En validant, vous confirmez avoir 18 ans ou plus et accepter les
                    <a href="{{ route('pages.terms') }}" style="border-bottom:1px solid var(--gold);">conditions générales</a>.
                </p>

                @if ($contact->hasWhatsapp() || $contact->hasPhone())
                    <p class="summary-note">
                        Une question ?
                        @if ($contact->hasWhatsapp())
                            <a href="{{ $contact->whatsappHref('Bonjour, j\'ai une question sur ma commande en cours.') }}" style="border-bottom:1px solid var(--gold);">WhatsApp</a>
                        @endif
                        @if ($contact->hasPhone())
                            · <a href="{{ $contact->telHref() }}" style="border-bottom:1px solid var(--gold);">Appeler</a>
                        @endif
                    </p>
                @endif
            </aside>
        </div>
    </div>
@endsection
