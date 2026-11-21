@extends('layouts.store')

@section('content')
  <section class="shop-intro">
    <div class="eyebrow">The candle atelier · Est. with care</div>
    <h1>Small flames.<br><em>Lasting impressions.</em></h1>
    <p>Hand-poured candles for slow evenings, thoughtful gifting and the moments worth keeping.</p>
    <a class="text-link" href="#collection">Shop the collection <span aria-hidden="true">↓</span></a>
    <div class="shop-note"><span>01 / 03</span><span>Made to order in India</span></div>
    <div class="hero-photo" role="img" aria-label="Warmly lit handcrafted candle"></div>
  </section>
  <section class="promise-bar" aria-label="Store benefits"><span>Hand-poured, made to order</span><span>Custom colour & fragrance</span><span>Complimentary delivery over ₹1,499</span></section>
  <section class="collection-section" id="collection">
    <div class="section-heading"><div><div class="eyebrow">Find your little ritual</div><h2>The collection</h2></div><p>Designed by hand. Made for your home.</p></div>
    <form class="catalog-tools" method="GET" action="{{ route('shop') }}">
      <div class="category-tabs" role="group" aria-label="Filter by collection">
        <a class="{{ !$category ? 'selected' : '' }}" href="{{ route('shop') }}">All</a>
        <a class="{{ $category === 'urli' ? 'selected' : '' }}" href="{{ route('shop', ['category' => 'urli']) }}">Urli</a>
        <a class="{{ $category === 'soy' ? 'selected' : '' }}" href="{{ route('shop', ['category' => 'soy']) }}">Soy wax</a>
      </div>
      <div class="tool-fields">
        <label class="search-field"><span class="sr-only">Search products</span><input type="search" name="q" placeholder="Search candles" value="{{ $search }}"></label>
        <label class="select-field"><span class="sr-only">Sort products</span><select name="sort" onchange="this.form.submit()"><option value="featured" @selected($sort === 'featured')>Featured</option><option value="price-low" @selected($sort === 'price-low')>Price: low to high</option><option value="price-high" @selected($sort === 'price-high')>Price: high to low</option><option value="name" @selected($sort === 'name')>Name</option></select></label>
        <button class="text-link" type="submit">Search</button>
      </div>
    </form>
    <div class="tag-filters" aria-label="Filter by product tag">
      <span>Popular:</span>
      @foreach (['Bestseller', 'Gift-ready', 'Floral', 'Festive'] as $popularTag)
        <a class="{{ $tag === $popularTag ? 'selected' : '' }}" href="{{ route('shop', array_filter(['tag' => $tag === $popularTag ? null : $popularTag, 'category' => $category, 'q' => $search, 'sort' => $sort])) }}">{{ $popularTag }}</a>
      @endforeach
      <a href="{{ route('shop') }}">Everything</a>
    </div>
    <p class="result-count">Showing {{ $products->count() }} of {{ $products->total() }} pieces</p>
    <div class="product-grid">
      @forelse ($products as $index => $product)
        <article class="product-card">
          <a class="product-image" href="{{ route('products.show', $product) }}" aria-label="View {{ $product->name }}">
            <img src="{{ $product->image }}" alt="{{ $product->name }}" loading="lazy">
            <span class="image-index">{{ str_pad((string) ($products->firstItem() + $index), 2, '0', STR_PAD_LEFT) }}</span>
          </a>
          <div class="product-copy">
            <div class="product-meta"><span>{{ $product->code }}</span><span>{{ $product->category === 'urli' ? 'Urli candle' : 'Soy wax' }}</span></div>
            <h3><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h3>
            <div class="tag-list">@foreach ($product->tags ?? [] as $productTag)<span class="tag">{{ $productTag }}</span>@endforeach</div>
            <div class="product-buy"><div><strong>₹{{ number_format($product->price) }}</strong><small class="stock-label">{{ $product->stock_quantity > 0 ? $product->stock_quantity.' in stock' : 'Out of stock' }}</small></div>
              @if ($product->stock_quantity > 0)
                <form method="POST" action="{{ route('cart.add', $product) }}">@csrf<button class="icon-button add-button" type="submit" aria-label="Add {{ $product->name }} to cart">+</button></form>
              @else
                <button class="icon-button add-button" type="button" disabled aria-label="Out of stock">+</button>
              @endif
            </div>
          </div>
        </article>
      @empty
        <div class="no-results"><h3>Nothing by that name just yet.</h3><a class="text-link" href="{{ route('shop') }}">Reset filters</a></div>
      @endforelse
    </div>
    {{ $products->links() }}
  </section>
  <section class="custom-callout"><div><div class="eyebrow">Made personal</div><h2>A little more <em>you.</em></h2><p>Choose a colour and fragrance for a candle that feels like it was always yours.</p></div><a class="button button-light" href="https://wa.me/918527479492?text={{ rawurlencode('Hi Rama Aurantha, I would like to discuss a custom candle.') }}" target="_blank" rel="noopener">Ask about custom orders <span aria-hidden="true">↗</span></a></section>
@endsection
