@extends('admin::layouts.app')

@section('title', 'Commandes')

@section('content')
    <h1>Commandes</h1>
    <div class="card">
        <form method="GET" class="actions">
            <select name="status">
                <option value="">Tous statuts</option>
                @foreach (['pending','awaiting_payment','paid','preparing','out_for_delivery','delivered','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Filtrer</button>
        </form>
    </div>
    <div class="card">
        <table>
            <thead><tr><th>N°</th><th>Client</th><th>Total</th><th>Statut</th><th>Paiement</th><th>Date</th><th></th></tr></thead>
            <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td><code>{{ $order->order_number }}</code></td>
                    <td>{{ $order->customer_name }}<br><span class="muted">{{ $order->customer_email }}</span></td>
                    <td>{{ $order->formattedTotal() }}</td>
                    <td><span class="badge">{{ $order->status }}</span></td>
                    <td>{{ $order->payment_method }}</td>
                    <td>{{ $order->placed_at?->format('d/m/Y H:i') }}</td>
                    <td><a href="{{ route('admin.orders.show', $order) }}">Voir</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $orders->links() }}
    </div>
@endsection
