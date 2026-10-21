@extends('catalog::layouts.app')

@section('title', 'Votre panier — Nasande Vins')
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li aria-current="page">Panier</li>
                </ol>
            </nav>
            <h1>Votre panier</h1>
            @if (($lines ?? []) !== [])
                <p class="page-hero-meta">{{ $count }} bouteille{{ $count > 1 ? 's' : '' }}</p>
            @endif
        </div>
    </section>

    <div class="container">
        @if (session('success'))
            <div class="alert alert-success" style="margin-top:1.5rem;">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error" style="margin-top:1.5rem;">{{ session('error') }}</div>
        @endif

        @if (($lines ?? []) === [])
            <div class="empty-cart" style="margin:2rem 0 4rem;">
                <h1 style="font-size:1.9rem;">Votre panier est vide</h1>
                <p>La cave vous attend : commencez par les incontournables ou par une catégorie.</p>
                <p style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
                    <a class="btn" href="{{ route('wines.index') }}">Découvrir la cave</a>
                    <a class="btn btn-outline-dark" href="{{ route('wines.category', 'champagnes') }}">Voir les champagnes</a>
                </p>
            </div>
        @else
            <div class="cart-layout">
                <div>
                    <div class="cart-lines">
                        @foreach ($lines as $line)
                            @php
                                $item = $line['item'];
                                $variant = $line['variant'] ?? null;
                                $product = $variant?->product;
                                $spec = is_array($product?->specifications) ? $product->specifications : [];
                                $img = $product?->primaryImage();
                            @endphp
                            <div class="cart-line">
                                <div class="cart-line-media">
                                    @if ($img)
                                        <img src="{{ $img->url }}" alt="{{ $img->alt_text ?: $item['title'] }}" loading="lazy" width="96" height="120">
                                    @else
                                        <img src="{{ asset('images/vins/placeholder.svg') }}" alt="" loading="lazy" width="96" height="120">
                                    @endif
                                </div>
                                <div>
                                    @if ($product?->brand)
                                        <p class="cart-line-house">{{ $product->brand->name }}</p>
                                    @endif
                                    <p class="cart-line-name">
                                        @if ($product)
                                            <a href="{{ $product->canonical_path }}">{{ $item['title'] }}</a>
                                        @else
                                            {{ $item['title'] }}
                                        @endif
                                    </p>
                                    <p class="cart-line-meta">
                                        {{ collect([$spec['millesime'] ?? null, $spec['region'] ?? null, $spec['format'] ?? null])->filter()->implode(' · ') }}
                                    </p>
                                    <div class="cart-line-actions">
                                        <form method="POST" action="{{ route('cart.update', $item['variant_id']) }}" style="display:flex; gap:.4rem; align-items:center;">
                                            @csrf
                                            @method('PATCH')
                                            <label for="qty-{{ $item['variant_id'] }}" class="sr-only">Quantité</label>
                                            <input id="qty-{{ $item['variant_id'] }}" type="number" name="quantity" value="{{ $item['quantity'] }}" min="0" max="99"
                                                   style="width:3rem; padding:.35rem; border:1px solid var(--line); background:#fff; text-align:center; font-size:.8rem;">
                                            <button type="submit" class="cart-remove">Mettre à jour</button>
                                        </form>
                                        <form method="POST" action="{{ route('cart.remove', $item['variant_id']) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cart-remove">Retirer</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="cart-line-price">
                                    {{ number_format($item['price_minor'] * $item['quantity'], 0, ',', ' ') }} FCFA
                                    <span class="cart-line-unit">{{ number_format($item['price_minor'], 0, ',', ' ') }} FCFA / bouteille</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <p style="margin-top:1.5rem;">
                        <a class="link-arrow" href="{{ route('wines.index') }}">Continuer la sélection</a>
                    </p>
                </div>

                <aside class="summary" aria-label="Récapitulatif">
                    <h2>Récapitulatif</h2>
                    <div class="summary-row"><span>Sous-total</span><span>{{ number_format($subtotal, 0, ',', ' ') }} FCFA</span></div>
                    <div class="summary-row"><span>Livraison ({{ config('commerce.delivery.city', 'Brazzaville') }})</span><span>{{ number_format($shipping, 0, ',', ' ') }} FCFA</span></div>
                    <div class="summary-row total"><span>Total</span><span>{{ number_format($total, 0, ',', ' ') }} FCFA</span></div>
                    <a class="btn btn-block" href="{{ route('checkout.show') }}">Passer commande</a>
                    <p class="summary-note">Paiement Airtel Money, MTN MoMo ou à la livraison. Le livreur vous appelle avant de passer.</p>
                </aside>
            </div>
        @endif
    </div>
@endsection
