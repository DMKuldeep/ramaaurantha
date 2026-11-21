@extends('layouts.store')

@section('title', 'Your account | Rama Aurantha')
@section('content')
  <section class="page-shell account-shell">
    <div class="account-heading"><div><div class="eyebrow">Your Rama Aurantha</div><h1 class="page-title">Hello, {{ Str::before($user->name, ' ') }}.</h1><p>{{ $user->email }}</p></div>
      <form method="POST" action="{{ route('logout') }}">@csrf<button class="remove-link account-signout" type="submit">Sign out</button></form>
    </div>
    <div class="address-book">
      <div class="address-book-heading"><div><div class="eyebrow">Your profile</div><h2>Contact details</h2></div></div>
      <form class="admin-form" method="POST" action="{{ route('account.update') }}">@csrf @method('PATCH')
        <label class="field"><span>Your name</span><input name="name" required maxlength="120" value="{{ old('name', $user->name) }}"></label>
        <label class="field"><span>Mobile number</span><input name="phone" type="tel" pattern="[0-9+][0-9+ ]{7,23}" value="{{ old('phone', $user->phone) }}"></label>
        <button class="button" type="submit">Save profile</button>
      </form>
    </div>
    <div class="address-book">
      <div class="address-book-heading"><div><div class="eyebrow">Saved for next time</div><h2>Your addresses</h2></div><span>{{ $addresses->count() }} saved</span></div>
      @if ($addresses->isNotEmpty())
        <div class="saved-addresses">@foreach ($addresses as $address)
          <article class="saved-address"><span class="address-number">{{ $loop->iteration }}</span><div><strong>{{ $address->name }}</strong><p>{{ $address->address }}{{ $address->landmark ? ', '.$address->landmark : '' }}<br>{{ $address->city }}, {{ $address->state }} {{ $address->pincode }}<br>{{ $address->phone }}</p></div>
            <form method="POST" action="{{ route('addresses.destroy', $address) }}">@csrf @method('DELETE')<button class="remove-link" type="submit">Remove</button></form>
          </article>
        @endforeach</div>
      @endif
      <form class="admin-form" method="POST" action="{{ route('addresses.store') }}">@csrf
        <h3>Add a delivery address</h3>
        <label class="field"><span>Full name</span><input name="name" required maxlength="120"></label>
        <label class="field"><span>Mobile number</span><input name="phone" required type="tel" pattern="[0-9+][0-9+ ]{7,23}"></label>
        <label class="field"><span>Address</span><input name="address" required maxlength="500"></label>
        <label class="field"><span>Landmark</span><input name="landmark" maxlength="160"></label>
        <div class="form-grid"><label class="field"><span>City</span><input name="city" required maxlength="100"></label><label class="field"><span>State</span><input name="state" required maxlength="100"></label><label class="field"><span>PIN code</span><input name="pincode" required pattern="[0-9]{6}" maxlength="6"></label></div>
        <button class="button" type="submit">Save address</button>
      </form>
    </div>
    <div class="address-book">
      <div class="address-book-heading"><div><div class="eyebrow">Order history</div><h2>Your recent orders</h2></div></div>
      @forelse ($orders as $order)
        <article class="saved-address"><div><strong><a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></strong><p>{{ $order->created_at->format('d M Y') }} · {{ ucfirst($order->status) }} · ₹{{ number_format($order->total) }}</p></div></article>
      @empty
        <p>No orders yet. Your order history will appear here.</p>
      @endforelse
    </div>
  </section>
@endsection
