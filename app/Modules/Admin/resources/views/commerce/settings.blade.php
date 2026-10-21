@extends('admin::layouts.app')

@section('title', 'Paramètres commerce')

@section('content')
    <h1>Paramètres commerce</h1>
    <form method="POST" action="{{ route('admin.commerce.settings.update') }}" class="card">
        @csrf @method('PUT')
        <p class="muted">Livraison : {{ $deliveryCity }} uniquement (V1).</p>
        <label for="shipping_fee_minor">Frais de livraison (XAF)</label>
        <input id="shipping_fee_minor" type="number" name="shipping_fee_minor" min="0" value="{{ old('shipping_fee_minor', $shippingFee) }}" required>

        <h2 style="margin-top:1.5rem; font-size:1.1rem;">Coordonnées boutique</h2>
        <p class="muted">Affichées dans le header, le footer, les fiches produit et le checkout. Laisser vide pour masquer le lien.</p>

        <label for="contact_phone">Téléphone (affichage + appel)</label>
        <input id="contact_phone" type="text" name="contact_phone" value="{{ old('contact_phone', $contactPhone) }}" placeholder="+242 06 XXX XX XX">

        <label for="contact_whatsapp">WhatsApp (indicatif pays, chiffres)</label>
        <input id="contact_whatsapp" type="text" name="contact_whatsapp" value="{{ old('contact_whatsapp', $contactWhatsapp) }}" placeholder="24206XXXXXXX">

        <label for="contact_email">Email de contact</label>
        <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', $contactEmail) }}" placeholder="bonjour@Nasande Vins">

        <button class="btn btn-primary" type="submit" style="margin-top:1rem;">Enregistrer</button>
    </form>
@endsection
