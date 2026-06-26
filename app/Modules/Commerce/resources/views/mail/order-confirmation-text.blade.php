Confirmation de commande {{ $order->order_number }}

Bonjour {{ $order->customer_name }},

Merci pour votre commande sur nasande.cg.

Numéro : {{ $order->order_number }}
Total : {{ number_format($order->total_minor, 0, ',', ' ') }} {{ $order->currency_code }}
Livraison : {{ $order->delivery_city }}
Adresse : {{ $order->delivery_address_text }}
Paiement : {{ $order->payment_method }}

Articles :
@foreach ($order->items as $item)
- {{ $item->title }} × {{ $item->quantity }} = {{ number_format($item->line_total_minor, 0, ',', ' ') }} {{ $order->currency_code }}
@endforeach

Frais de livraison : {{ number_format($order->shipping_minor, 0, ',', ' ') }} {{ $order->currency_code }}

Statut : {{ $order->status }}

— nasande.cg
