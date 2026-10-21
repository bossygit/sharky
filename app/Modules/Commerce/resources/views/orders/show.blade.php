@extends('catalog::layouts.app')

@section('title', 'Commande '.$order->order_number.' — Nasande Vins')
@section('robots', 'noindex, nofollow')

@section('content')
    @php
        $paymentLabels = [
            'cash_on_delivery' => 'Paiement à la livraison',
            'airtel_money' => 'Airtel Money',
            'mtn_momo' => 'MTN Mobile Money',
        ];
        $statusLabels = [
            'pending' => 'En attente de confirmation',
            'confirmed' => 'Confirmée',
            'preparing' => 'En préparation',
            'shipped' => 'En livraison',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
        ];
        $paymentStatusLabels = [
            'pending' => 'en attente',
            'paid' => 'payé',
            'failed' => 'échoué',
            'refunded' => 'remboursé',
        ];
        $paymentLabel = $paymentLabels[$order->payment_method] ?? $order->payment_method;
        $statusLabel = $statusLabels[$order->status] ?? $order->status;
        $paymentStatusLabel = $paymentStatusLabels[$order->payment_status] ?? $order->payment_status;
        $totalLabel = number_format((int) $order->total_minor, 0, ',', ' ') . ' FCFA';
    @endphp
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow">Commande enregistrée</p>
            <h1>Merci, {{ explode(' ', trim($order->customer_name))[0] }}.</h1>
            <p class="lede">
                Votre commande <strong>{{ $order->order_number }}</strong> est bien reçue.
                Nous vous appelons pour confirmer la livraison à {{ $order->delivery_city }}.
            </p>
        </div>
    </section>

    <div class="container">
        @if (session('success'))
            <div class="alert alert-success" style="margin-top:1.5rem;">{{ session('success') }}</div>
        @endif

        <div class="checkout-grid">
            <div>
                <div class="form-block">
                    <h2>Récapitulatif</h2>
                    <div class="summary-row"><span>Numéro</span><span>{{ $order->order_number }}</span></div>
                    <div class="summary-row"><span>Statut</span><span>{{ $statusLabel }}</span></div>
                    <div class="summary-row"><span>Paiement</span><span>{{ $paymentLabel }} ({{ $paymentStatusLabel }})</span></div>
                    <div class="summary-row"><span>Livraison</span><span>{{ $order->delivery_city }}</span></div>
                    <div class="summary-row"><span>Adresse</span><span style="text-align:right;">{{ $order->delivery_address_text }}</span></div>
                    <div class="summary-row total"><span>Total</span><span>{{ $totalLabel }}</span></div>
                </div>

                @if ($instructions)
                    <div class="form-block" style="border-color:var(--gold); background:var(--cream-2);">
                        <h2>Instructions de paiement</h2>
                        <p style="margin:0; color:var(--ink-soft);">{{ $instructions }}</p>
                    </div>
                @endif

                <div class="form-block">
                    <h2>Vos bouteilles</h2>
                    <ul style="border-top:1px solid var(--line);">
                        @foreach ($order->items as $item)
                            <li style="display:flex; justify-content:space-between; gap:1rem; padding:.75rem 0; border-bottom:1px solid var(--line); font-size:.88rem;">
                                <span>{{ $item->title }} × {{ $item->quantity }}</span>
                                <span style="white-space:nowrap;">{{ number_format($item->line_total_minor, 0, ',', ' ') }} FCFA</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="muted" style="font-size:.8rem;">
                    Un email de confirmation a été envoyé à {{ $order->customer_email }}.
                </p>

                <p style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:1.5rem;">
                    <a class="btn" href="{{ route('wines.index') }}">Continuer la sélection</a>
                    <a class="btn btn-outline-dark" href="{{ route('pages.contact') }}">Une question ?</a>
                </p>
            </div>

            <aside class="summary">
                <h2>Et maintenant ?</h2>
                <p style="font-size:.85rem; color:var(--ink-soft);">
                    Nous préparons votre commande et vous appelons au numéro indiqué.
                    Le livreur vous prévient avant de passer.
                </p>
                <p class="summary-note">
                    Conservez votre numéro de commande : il vous sera demandé pour tout échange ou retour.
                </p>
                <p class="summary-note">
                    L'abus d'alcool est dangereux pour la santé. À consommer avec modération.
                </p>
            </aside>
        </div>
    </div>
@endsection
