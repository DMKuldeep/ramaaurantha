@extends('layouts.store')

@section('title', 'Order received | Rama Aurantha')
@section('content')
  <section class="confirmation-page">
    <div class="confirmation-stamp" aria-hidden="true">RA</div><div class="eyebrow">Order request received</div>
    <h1>Thank you, {{ Str::before($order->customer_name, ' ') }}.</h1>
    <p>We have saved your order request. Our team will confirm availability and delivery timing.</p>
    <div class="confirmation-number"><span>Order reference</span><strong>{{ $order->order_number }}</strong></div>
    <div class="confirmation-total"><span>Order total</span><strong>₹{{ number_format($order->total) }}</strong></div>
    <p class="confirmation-payment">Cash on delivery selected. We will email your order details to {{ $order->email }}.</p>
    <a class="button" href="https://wa.me/918527479492?text={{ rawurlencode('Hi Rama Aurantha, my order reference is '.$order->order_number.'.') }}" target="_blank" rel="noopener">Contact us on WhatsApp <span aria-hidden="true">↗</span></a>
    <a class="text-link" href="{{ route('shop') }}">Back to the collection</a>
  </section>
@endsection
