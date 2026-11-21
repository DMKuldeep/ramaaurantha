@extends('layouts.store')

@section('title', 'Sign in | Rama Aurantha')
@section('content')
  <section class="account-shell page-shell">
    <div class="account-intro"><div class="eyebrow">Your Rama Aurantha</div><h1 class="page-title">Welcome back.</h1><p>Sign in with a one-time code sent to your email. Save addresses and follow your orders.</p></div>
    <div class="account-form">
      @if (session('otp_sent'))
        <h2>{{ config('mail.default') === 'log' ? 'Local email testing' : 'Check your inbox' }}</h2><p>{{ session('otp_sent') }}</p>
        <form method="POST" action="{{ route('login.otp.verify') }}">
          @csrf
          <label class="field"><span>Email address</span><input name="email" type="email" required value="{{ old('email', session('otp_email')) }}"></label>
          <label class="field"><span>6-digit code</span><input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus></label>
          <button class="button full-width" type="submit">Verify and sign in <span aria-hidden="true">→</span></button>
        </form>
        <form method="POST" action="{{ route('login.otp.send') }}" class="otp-resend">@csrf
          <input type="hidden" name="email" value="{{ session('otp_email') }}">
          <input type="hidden" name="name" value="{{ session('otp_name') }}">
          <button class="text-link" type="submit">Send a new code</button>
        </form>
      @else
        <form method="POST" action="{{ route('login.otp.send') }}">
          @csrf
          <h2>Sign in or create your profile</h2>
          <label class="field"><span>Your name <small>Optional for returning customers</small></span><input name="name" autocomplete="name" maxlength="120" value="{{ old('name') }}" placeholder="Name"></label>
          <label class="field"><span>Email address</span><input name="email" type="email" autocomplete="email" required maxlength="255" value="{{ old('email') }}" placeholder="you@example.com"></label>
          <button class="button full-width" type="submit">Email me a sign-in code <span aria-hidden="true">→</span></button>
          <p class="account-note">The code expires in {{ config('store.otp_expiry_minutes') }} minutes. We never ask for your email password.</p>
        </form>
      @endif
    </div>
  </section>
@endsection
