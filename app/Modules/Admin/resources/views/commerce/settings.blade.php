@extends('admin::layouts.app')

@section('title', 'Paramètres commerce')

@section('content')
    <h1>Paramètres commerce</h1>
    <form method="POST" action="{{ route('admin.commerce.settings.update') }}" class="card">
        @csrf @method('PUT')
        <p class="muted">Livraison : {{ $deliveryCity }} uniquement (V1).</p>
        <label for="shipping_fee_minor">Frais de livraison (XAF)</label>
        <input id="shipping_fee_minor" type="number" name="shipping_fee_minor" min="0" value="{{ old('shipping_fee_minor', $shippingFee) }}" required>
        <button class="btn btn-primary" type="submit" style="margin-top:1rem;">Enregistrer</button>
    </form>
@endsection
