@extends('layouts.store')

@section('title', 'Store admin | Rama Aurantha')
@section('content')
  <section class="page-shell admin-shell">
    <div class="admin-heading"><div><div class="eyebrow">Studio catalogue</div><h1 class="page-title">Store admin</h1><p>Signed in as {{ auth()->user()->name }} · {{ auth()->user()->role }}</p></div><div class="admin-heading-actions"><a class="text-link" href="{{ route('shop') }}">View storefront ↗</a><form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="remove-link" type="submit">Sign out</button></form></div></div>
    <div class="admin-notice"><strong>Shared catalogue and inventory.</strong> Changes are visible to every storefront visitor.</div>
    <div class="admin-layout">
      <form class="admin-form" method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="admin-form-heading"><div class="eyebrow">New listing</div><h2>Add a product</h2></div>
        <label class="field"><span>Product code</span><input name="code" required maxlength="24" value="{{ old('code') }}" placeholder="U-15"></label>
        <label class="field"><span>Product name</span><input name="name" required maxlength="90" value="{{ old('name') }}" placeholder="Lotus Pond Urli"></label>
        <div class="form-grid"><label class="field"><span>Collection</span><select name="category"><option value="urli">Urli candle</option><option value="soy">Soy wax candle</option></select></label><label class="field"><span>Price (₹)</span><input name="price" type="number" min="1" step="1" required></label></div>
        <label class="field"><span>Available inventory (units)</span><input name="stock_quantity" type="number" min="0" max="1000000" required value="0"></label>
        <label class="field"><span>Tags <small>Separate with commas</small></span><input name="tags" maxlength="120" placeholder="Floral, Gift-ready"></label>
        <label class="field"><span>Upload product image <small>PNG, JPEG or WebP, max 5 MB</small></span><input name="image_file" type="file" accept="image/png,image/jpeg,image/webp"></label>
        <label class="field"><span>Or use an HTTPS image URL</span><input name="image_url" type="url" placeholder="https://example.com/product.jpg"></label>
        <button class="button" type="submit">Add listing <span aria-hidden="true">→</span></button>
      </form>
      <section class="admin-listing-section"><div class="admin-listing-heading"><div><div class="eyebrow">{{ $products->count() }} listings</div><h2>Your products</h2></div></div>
        <div class="admin-listings">@forelse ($products as $product)
          <article class="admin-listing"><img src="{{ $product->image }}" alt="" loading="lazy"><div class="admin-listing-copy"><span>{{ $product->code }} · {{ $product->category === 'urli' ? 'Urli' : 'Soy wax' }}</span><h3>{{ $product->name }}</h3><strong>₹{{ number_format($product->price) }}</strong><small class="stock-label">{{ $product->stock_quantity }} in stock</small></div>
            <div class="admin-listing-actions"><a class="admin-action" href="{{ route('admin.products.edit', $product) }}">Edit</a>@if (auth()->user()->role === 'owner')<form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">@csrf @method('DELETE')<button class="admin-action admin-delete" type="submit">Delete</button></form>@endif</div>
          </article>
        @empty <p>No products yet. Add your first listing.</p> @endforelse</div>
      </section>
    </div>
    @if (auth()->user()->role === 'owner')
      <form class="admin-form coadmin-form" method="POST" action="{{ route('admin.team.store') }}">@csrf
        <div class="admin-form-heading"><div class="eyebrow">Team access</div><h2>Add an administrator</h2></div>
        <p>Create a separate password login for a trusted helper.</p>
        <div class="form-grid"><label class="field"><span>Name</span><input name="name" required maxlength="120"></label><label class="field"><span>Email address</span><input name="email" type="email" required></label></div>
        <label class="field"><span>Temporary password</span><input name="password" type="password" required minlength="12" maxlength="200" autocomplete="new-password"></label>
        <button class="button" type="submit">Create admin account</button>
      </form>
    @endif
    <section class="admin-listing-section">
      <div class="admin-listing-heading"><div><div class="eyebrow">{{ $orders->total() }} orders</div><h2>Customer orders</h2></div></div>
      <div class="admin-orders">@forelse ($orders as $order)
        <article class="admin-order">
          <div class="admin-order-heading"><div><strong>{{ $order->order_number }}</strong><p>{{ $order->customer_name }} · {{ $order->email }} · {{ $order->phone }}</p></div><strong>₹{{ number_format($order->total) }}</strong></div>
          <p>{{ $order->address }}, {{ $order->city }}, {{ $order->state }} {{ $order->pincode }}</p>
          <ul>@foreach ($order->items as $item)<li>{{ $item->product_name }} × {{ $item->quantity }} — ₹{{ number_format($item->unit_price * $item->quantity) }}</li>@endforeach</ul>
          <form method="POST" action="{{ route('admin.orders.update', $order) }}">@csrf @method('PATCH')
            <label class="field"><span>Order status</span><select name="status" @disabled($order->inventory_released_at)><option value="placed" @selected($order->status === 'placed')>Placed</option>@foreach (['confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)<option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
            @if (!$order->inventory_released_at)<button class="admin-action" type="submit">Update status</button>@else<span>Cancelled · stock restored</span>@endif
          </form>
        </article>
      @empty <p>No orders have been placed yet.</p> @endforelse</div>
      {{ $orders->links() }}
    </section>
  </section>
@endsection
