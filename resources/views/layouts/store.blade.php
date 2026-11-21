<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#f7f6ef">
  <meta name="description" content="Discover handcrafted, made-to-order candles by Rama Aurantha.">
  <title>@yield('title', 'Rama Aurantha | Handcrafted Candles')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
</head>
<body>
  <div class="announcement">Complimentary delivery on orders over ₹1,499 <span aria-hidden="true">·</span> Made to order</div>
  <header class="site-header">
    <a class="wordmark" href="{{ route('shop') }}" aria-label="Rama Aurantha home">Rama Aurantha<span>Handcrafted candle studio</span></a>
    <nav class="main-nav" aria-label="Main navigation">
      <a href="{{ route('shop') }}">Shop</a>
      <a href="{{ route('shop') }}#collection">Collection</a>
      <a href="https://wa.me/918527479492?text=Hi%20Rama%20Aurantha%2C%20I%27d%20like%20to%20ask%20about%20a%20custom%20order." target="_blank" rel="noopener">Custom orders <span aria-hidden="true">↗</span></a>
    </nav>
    <div class="header-actions">
      <a href="https://instagram.com/rama_aurantha" target="_blank" rel="noopener">Instagram <span aria-hidden="true">↗</span></a>
      @auth
        <a class="account-link" href="{{ route('account') }}">Account</a>
      @else
        <a class="account-link" href="{{ route('login') }}">Sign in</a>
      @endauth
      <a class="bag-link" href="{{ route('cart.index') }}">Bag <span>{{ collect(session('cart', []))->sum() }}</span></a>
    </div>
  </header>

  @if (session('success'))
    <div class="store-alert store-alert-success" role="status">{{ session('success') }}</div>
  @endif
  @if ($errors->any())
    <div class="store-alert store-alert-error" role="alert">{{ $errors->first() }}</div>
  @endif

  <main id="app" tabindex="-1">
    @yield('content')
  </main>

  <footer class="site-footer" id="contact">
    <div>
      <div class="footer-brand">Rama Aurantha</div>
      <p>Hand-poured candles for slow evenings, thoughtful gifting and the moments worth keeping.</p>
    </div>
    <div class="footer-col">
      <strong>Explore</strong>
      <a href="{{ route('shop') }}">Shop all candles</a>
      <a href="{{ route('cart.index') }}">Your shopping bag</a>
      <a href="{{ route('admin.login') }}">Product admin</a>
      <a href="https://instagram.com/rama_aurantha" target="_blank" rel="noopener">Instagram ↗</a>
    </div>
    <div class="footer-col">
      <strong>Here to help</strong>
      <a href="https://wa.me/918527479492" target="_blank" rel="noopener">WhatsApp +91 85274 79492 ↗</a>
      <span>Our team will confirm your COD order and delivery details.</span>
    </div>
    <div class="footer-bottom"><span>© {{ date('Y') }} Rama Aurantha</span><span>Small flames. Lasting impressions.</span></div>
  </footer>
</body>
</html>
