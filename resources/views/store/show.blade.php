@extends('layouts.store')

@section('title', $product->name.' | Rama Aurantha')
@section('content')
  <section class="detail-layout">
    <a class="back-link" href="{{ route('shop') }}">← Back to collection</a>
    <div class="detail-image"><img src="{{ $product->image }}" alt="{{ $product->name }}"></div>
    <div class="detail-copy">
      <div class="eyebrow">{{ $product->code }} · {{ $product->category === 'urli' ? 'Urli candle' : 'Soy wax candle' }}</div>
      <h1>{{ $product->name }}</h1><div class="detail-price">₹{{ number_format($product->price) }}</div>
      <p>{{ $product->stock_quantity > 0 ? $product->stock_quantity.' available' : 'Currently out of stock' }}. Hand-finished in small batches, with considered details and a soft, warm glow.</p>
      <div class="tag-list">@foreach ($product->tags ?? [] as $tag)<span class="tag">{{ $tag }}</span>@endforeach</div>
      @if ($product->stock_quantity > 0)
        <form method="POST" action="{{ route('cart.add', $product) }}">@csrf<label class="field"><span>Quantity</span><input type="number" name="quantity" min="1" max="{{ min(99, $product->stock_quantity) }}" value="1"></label><button class="button" type="submit">Add to bag <span aria-hidden="true">+</span></button></form>
      @else
        <button class="button" type="button" disabled>Out of stock</button>
      @endif
      <p class="detail-note">Complimentary delivery on orders over ₹1,499. Made to order; dispatch timing is confirmed after purchase.</p>
      <a class="text-link" href="{{ route('cart.index') }}">View your bag</a>
    </div>
  </section>
@endsection
