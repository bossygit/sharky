@extends('catalog::layouts.app')

@section('title', 'Commande '.$order->order_number)

@section('content')
<section class="container" style="padding:2rem 0; max-width:720px;">
    <h1>Merci pour votre commande</h1>
    @if (session('success'))
        <p style="color:var(--c-success);">{{ session('success') }}</p>
    @endif

    <div style="border:1px solid var(--c-border); border-radius:8px; padding:1.25rem; margin:1rem 0;">
        <p><strong>Numéro :</strong> {{ $order->order_number }}</p>
        <p><strong>Total :</strong> {{ $order->formattedTotal() }}</p>
        <p><strong>Statut :</strong> {{ $order->status }}</p>
        <p><strong>Paiement :</strong> {{ $order->payment_method }} ({{ $order->payment_status }})</p>
        <p><strong>Livraison :</strong> {{ $order->delivery_city }}</p>
        <p><strong>Adresse :</strong> {{ $order->delivery_address_text }}</p>
    </div>

    @if ($instructions)
        <div style="background:#fffbeb; border:1px solid #fcd34d; padding:1rem; border-radius:8px; margin:1rem 0;">
            <h2 style="font-size:1.1rem; margin-top:0;">Instructions de paiement</h2>
            <p>{{ $instructions }}</p>
        </div>
    @endif

    <h2>Articles</h2>
    <ul>
        @foreach ($order->items as $item)
            <li>{{ $item->title }} × {{ $item->quantity }} — {{ number_format($item->line_total_minor, 0, ',', ' ') }} XAF</li>
        @endforeach
    </ul>

    <p class="muted" style="margin-top:2rem;">Un email de confirmation a été envoyé à {{ $order->customer_email }}.</p>
    <a href="{{ route('home') }}">Retour à l'accueil</a>
</section>
@endsection
