Nouvelle commande {{ $order->order_number }}

Client : {{ $order->customer_name }}
Email : {{ $order->customer_email }}
Téléphone : {{ $order->customer_phone }}
Adresse : {{ $order->delivery_address_text }}, {{ $order->delivery_city }}
Total : {{ number_format($order->total_minor, 0, ',', ' ') }} {{ $order->currency_code }}
Paiement : {{ $order->payment_method }} ({{ $order->payment_status }})

Articles :
@foreach ($order->items as $item)
- {{ $item->title }} × {{ $item->quantity }}
@endforeach

Admin : {{ url('/admin/orders/'.$order->id) }}
