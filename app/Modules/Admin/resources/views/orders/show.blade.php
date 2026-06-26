@extends('admin::layouts.app')

@section('title', 'Commande '.$order->order_number)

@section('content')
    <div class="actions" style="justify-content:space-between;">
        <h1 style="margin:0;">Commande {{ $order->order_number }}</h1>
        <a class="btn" href="{{ route('admin.orders.index') }}">← Retour</a>
    </div>

    <div class="grid" style="margin:1rem 0;">
        <div class="card"><div class="stat" style="font-size:1.1rem;">{{ $order->customer_name }}</div><div class="muted">{{ $order->customer_email }} · {{ $order->customer_phone }}</div></div>
        <div class="card"><div class="stat" style="font-size:1.1rem;">{{ $order->formattedTotal() }}</div><div class="muted">{{ $order->payment_method }} · {{ $order->payment_status }}</div></div>
    </div>

    <div class="card">
        <p><strong>Adresse :</strong> {{ $order->delivery_address_text }}, {{ $order->delivery_city }}</p>
        @if ($order->customer_notes)<p><strong>Notes :</strong> {{ $order->customer_notes }}</p>@endif
        <h3>Articles</h3>
        <table>
            <thead><tr><th>Produit</th><th>SKU</th><th>Qté</th><th>Total</th></tr></thead>
            <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->line_total_minor, 0, ',', ' ') }} XAF</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Mettre à jour le statut</h3>
        <form method="POST" action="{{ route('admin.orders.status', $order) }}">
            @csrf
            <label for="status">Statut commande</label>
            <select id="status" name="status">
                @foreach (['pending','awaiting_payment','paid','preparing','out_for_delivery','delivered','cancelled'] as $s)
                    <option value="{{ $s }}" @selected($order->status===$s)>{{ $s }}</option>
                @endforeach
            </select>
            <label for="payment_status">Statut paiement</label>
            <select id="payment_status" name="payment_status">
                <option value="">— inchangé —</option>
                @foreach (['pending','processing','paid','failed','refunded'] as $s)
                    <option value="{{ $s }}" @selected($order->payment_status===$s)>{{ $s }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit" style="margin-top:1rem;">Enregistrer</button>
        </form>
    </div>
@endsection
