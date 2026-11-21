<h1>Thank you for your order</h1>
<p>Order reference: <strong>{{ $order->order_number }}</strong></p>
<p>Payment method: Cash on delivery</p>
<ul>
  @foreach ($order->items as $item)
    <li>{{ $item->product_name }} × {{ $item->quantity }} — ₹{{ number_format($item->unit_price * $item->quantity) }}</li>
  @endforeach
</ul>
<p>Subtotal: ₹{{ number_format($order->subtotal) }}</p>
<p>Delivery: {{ $order->delivery ? '₹'.number_format($order->delivery) : 'Complimentary' }}</p>
<p><strong>Total: ₹{{ number_format($order->total) }}</strong></p>
<p>Delivery to {{ $order->customer_name }}: {{ $order->address }}, {{ $order->city }}, {{ $order->state }} {{ $order->pincode }}.</p>
<p>Our team will contact you to confirm availability and delivery timing.</p>
