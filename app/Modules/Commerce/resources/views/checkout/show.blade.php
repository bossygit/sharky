@extends('catalog::layouts.app')

@section('title', 'Checkout | '.config('app.name'))

@section('content')
<section class="container" style="padding:2rem 0; max-width:720px;">
    <h1>Finaliser la commande</h1>
    <p class="muted">Livraison à {{ $deliveryCity }} uniquement.</p>

    @if ($contact->hasWhatsapp() || $contact->hasPhone())
        <p class="checkout-help">
            Une question avant de valider ?
            @if ($contact->hasWhatsapp())
                <a href="{{ $contact->whatsappHref('Bonjour, j\'ai une question sur ma commande en cours sur nasande.cg.') }}">WhatsApp</a>
            @endif
            @if ($contact->hasPhone())
                · <a href="{{ $contact->telHref() }}">Appeler</a>
            @endif
        </p>
    @endif

    @if (session('error'))
        <p style="color:var(--c-danger);">{{ session('error') }}</p>
    @endif
    @if (session('success'))
        <p style="color:var(--c-success);">{{ session('success') }}</p>
    @endif

    <div class="card" style="border:1px solid var(--c-border); padding:1rem; border-radius:8px; margin:1rem 0;">
        <p>Total à payer : <strong>{{ number_format($total, 0, ',', ' ') }} XAF</strong></p>
        <p class="muted">Dont livraison {{ number_format($shipping, 0, ',', ' ') }} XAF</p>
    </div>

    <form method="POST" action="{{ route('checkout.store') }}" class="checkout-form">
        @csrf
        <label for="customer_name">Nom complet *</label>
        <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required>

        <label for="customer_email">Email *</label>
        <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}" required>

        <label for="customer_phone">Téléphone (Mobile Money) *</label>
        <input id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" required placeholder="+242 06 XXX XX XX">

        <label for="delivery_address_text">Adresse de livraison à Brazzaville *</label>
        <textarea id="delivery_address_text" name="delivery_address_text" required>{{ old('delivery_address_text') }}</textarea>

        <fieldset style="margin:1rem 0; border:1px solid var(--c-border); padding:1rem; border-radius:8px;">
            <legend>Mode de paiement</legend>
            @foreach ($paymentMethods as $method)
                <label style="display:block; margin:.5rem 0;">
                    <input type="radio" name="payment_method" value="{{ $method->code() }}" @checked(old('payment_method', 'cash_on_delivery') === $method->code()) required>
                    {{ $method->label() }}
                </label>
            @endforeach
        </fieldset>

        <label for="customer_notes">Notes (optionnel)</label>
        <textarea id="customer_notes" name="customer_notes">{{ old('customer_notes') }}</textarea>

        <button type="submit" style="margin-top:1rem; padding:.85rem 1.5rem; background:var(--c-primary); color:#fff; border:0; border-radius:8px; cursor:pointer; font-size:1rem;">
            Confirmer la commande
        </button>
    </form>
</section>
@endsection
