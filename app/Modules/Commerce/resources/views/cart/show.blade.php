@extends('catalog::layouts.app')

@section('title', 'Panier | '.config('app.name'))

@section('content')
<section class="container" style="padding:2rem 0;">
    <h1>Votre panier</h1>

    @if (session('success'))
        <p style="color:var(--c-success);">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p style="color:var(--c-danger);">{{ session('error') }}</p>
    @endif

    @if (($lines ?? []) === [] && ($items ?? []) === [])
        <p>Votre panier est vide.</p>
        <a href="{{ route('home') }}">Continuer vos achats</a>
    @else
        <table style="width:100%; margin:1.5rem 0;">
            <thead>
                <tr><th>Produit</th><th>Prix</th><th>Qté</th><th>Total</th><th></th></tr>
            </thead>
            <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line['item']['title'] }}</td>
                    <td>{{ number_format($line['item']['price_minor'], 0, ',', ' ') }} XAF</td>
                    <td>
                        <form method="POST" action="{{ route('cart.update', $line['item']['variant_id']) }}">
                            @csrf @method('PATCH')
                            <input type="number" name="quantity" value="{{ $line['item']['quantity'] }}" min="0" max="99" style="width:4rem;">
                            <button type="submit" class="btn" style="margin-left:.25rem;">OK</button>
                        </form>
                    </td>
                    <td>{{ number_format($line['item']['price_minor'] * $line['item']['quantity'], 0, ',', ' ') }} XAF</td>
                    <td>
                        <form method="POST" action="{{ route('cart.remove', $line['item']['variant_id']) }}">
                            @csrf @method('DELETE')
                            <button type="submit" style="color:var(--c-danger); background:none; border:0; cursor:pointer;">Retirer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div style="max-width:360px; margin-left:auto;">
            <p>Sous-total : <strong>{{ number_format($subtotal, 0, ',', ' ') }} XAF</strong></p>
            <p>Livraison (Brazzaville) : <strong>{{ number_format($shipping, 0, ',', ' ') }} XAF</strong></p>
            <p style="font-size:1.25rem;">Total : <strong>{{ number_format($total, 0, ',', ' ') }} XAF</strong></p>
            <a href="{{ route('checkout.show') }}" style="display:inline-block; margin-top:1rem; padding:.75rem 1.25rem; background:var(--c-primary); color:#fff; border-radius:8px;">Passer commande</a>
        </div>
    @endif
</section>
@endsection
