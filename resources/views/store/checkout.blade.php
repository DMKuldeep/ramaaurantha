@extends('layouts.store')

@section('title', 'Checkout | Rama Aurantha')
@section('content')
  @php
    $subtotal = $items->sum(fn ($item) => $item['product']->price * $item['quantity']);
    $delivery = $subtotal >= 1499 ? 0 : 99;
  @endphp
  <section class="page-shell checkout-shell">
    <div class="eyebrow">Almost yours</div><h1 class="page-title">Checkout</h1>
    <div class="checkout-layout">
      <form method="POST" action="{{ route('checkout.place') }}" class="checkout-form">
        @csrf
        <fieldset><legend><span>01</span> Contact</legend><div class="form-grid">
          <label class="field full"><span>Email address</span><input type="email" value="{{ auth()->user()->email }}" readonly></label>
          <label class="field full"><span>Mobile number</span><input name="phone" type="tel" autocomplete="tel" inputmode="tel" pattern="[0-9+][0-9+ ]{7,23}" required value="{{ old('phone', $defaultAddress?->phone ?? auth()->user()->phone) }}" placeholder="+91 98765 43210"></label>
        </div></fieldset>
        <fieldset><legend><span>02</span> Delivery address</legend><div class="form-grid">
          <label class="field full"><span>Full name</span><input name="name" autocomplete="name" required value="{{ old('name', $defaultAddress?->name ?? auth()->user()->name) }}"></label>
          <label class="field full"><span>Address</span><input name="address" autocomplete="street-address" required value="{{ old('address', $defaultAddress?->address) }}" placeholder="House / flat, street and area"></label>
          <label class="field"><span>City</span><input name="city" autocomplete="address-level2" required value="{{ old('city', $defaultAddress?->city) }}"></label>
          <label class="field"><span>State</span><input name="state" autocomplete="address-level1" required value="{{ old('state', $defaultAddress?->state) }}"></label>
          <label class="field"><span>PIN code</span><input name="pincode" autocomplete="postal-code" inputmode="numeric" pattern="[0-9]{6}" required value="{{ old('pincode', $defaultAddress?->pincode) }}" placeholder="6 digits"></label>
          <label class="field"><span>Landmark <small>Optional</small></span><input name="landmark" value="{{ old('landmark', $defaultAddress?->landmark) }}"></label>
          <label class="field full"><span>Order note <small>Optional</small></span><textarea name="note" rows="3">{{ old('note') }}</textarea></label>
          <label class="save-address-option field full"><span><input type="checkbox" name="save_address" value="1" checked> Save this address to your account</span></label>
        </div></fieldset>
        <fieldset><legend><span>03</span> Payment</legend><div class="payment-options"><div class="payment-option"><span class="payment-mark">COD</span><span><strong>Cash on delivery</strong><small>Pay when your candles arrive. Our team will confirm availability.</small></span></div></div></fieldset>
        <button class="button full-width submit-order" type="submit">Place order request <span aria-hidden="true">→</span></button>
        <p class="form-footnote">We will email your order confirmation and contact you to confirm delivery.</p>
      </form>
      <aside class="order-summary checkout-summary"><h2>In your order <span>{{ $items->sum('quantity') }}</span></h2>
        @foreach ($items as $item)
          <div class="mini-line"><img src="{{ $item['product']->image }}" alt=""><div><strong>{{ $item['product']->name }}</strong><small>Qty {{ $item['quantity'] }}</small></div><span>₹{{ number_format($item['product']->price * $item['quantity']) }}</span></div>
        @endforeach
        <div class="summary-row"><span>Subtotal</span><span>₹{{ number_format($subtotal) }}</span></div><div class="summary-row"><span>Delivery</span><span>{{ $delivery ? '₹'.number_format($delivery) : 'Complimentary' }}</span></div><div class="summary-row summary-total"><span>Total</span><strong>₹{{ number_format($subtotal + $delivery) }}</strong></div><a class="continue-link" href="{{ route('cart.index') }}">Edit bag</a>
      </aside>
    </div>
  </section>
@endsection
