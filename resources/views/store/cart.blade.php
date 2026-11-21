@extends('layouts.store')

@section('title', 'Your bag | Rama Aurantha')
@section('content')
  <section class="page-shell">
    <div class="eyebrow">Your selection</div><h1 class="page-title">Shopping bag</h1>
    @if ($items->isNotEmpty())
      <div class="cart-layout"><div class="cart-lines">
        @foreach ($items as $item)
          <article class="cart-line">
            <a class="cart-thumb" href="{{ route('products.show', $item['product']) }}"><img src="{{ $item['product']->image }}" alt="{{ $item['product']->name }}"></a>
            <div class="cart-line-copy"><span class="eyebrow">{{ $item['product']->code }}</span><h2><a href="{{ route('products.show', $item['product']) }}">{{ $item['product']->name }}</a></h2><div class="line-price">₹{{ number_format($item['product']->price) }} · {{ $item['product']->stock_quantity }} in stock</div>
              <form method="POST" action="{{ route('cart.remove', $item['product']) }}">@csrf @method('DELETE')<button class="remove-link" type="submit">Remove</button></form>
            </div>
            <form class="quantity-control" method="POST" action="{{ route('cart.update', $item['product']) }}">@csrf @method('PATCH')<label class="sr-only" for="quantity-{{ $item['product']->id }}">Quantity</label><input id="quantity-{{ $item['product']->id }}" type="number" name="quantity" min="0" max="{{ min(99, $item['product']->stock_quantity) }}" value="{{ $item['quantity'] }}"><button type="submit">Update</button></form>
            <strong class="line-total">₹{{ number_format($item['product']->price * $item['quantity']) }}</strong>
          </article>
        @endforeach
      </div>
      <aside class="order-summary"><h2>Order summary</h2><div class="summary-row"><span>Subtotal</span><span>₹{{ number_format($subtotal) }}</span></div><div class="summary-row"><span>Delivery</span><span>{{ $delivery ? '₹'.number_format($delivery) : 'Complimentary' }}</span></div><p class="shipping-hint">{{ $subtotal >= 1499 ? 'Your delivery is on us.' : 'Add ₹'.number_format(1499 - $subtotal).' for complimentary delivery.' }}</p><div class="summary-row summary-total"><span>Total</span><strong>₹{{ number_format($subtotal + $delivery) }}</strong></div><a class="button full-width" href="{{ route('checkout') }}">Continue to checkout <span aria-hidden="true">→</span></a><a class="continue-link" href="{{ route('shop') }}">Continue shopping</a></aside></div>
    @else
      <div class="empty-state"><div class="empty-mark">RA</div><h2>Your bag is waiting for its first candle.</h2><p>Explore the collection and find one that feels like home.</p><a class="button" href="{{ route('shop') }}">Explore the collection</a></div>
    @endif
  </section>
@endsection
