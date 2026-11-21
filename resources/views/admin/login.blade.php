@extends('layouts.store')

@section('title', 'Admin sign in | Rama Aurantha')
@section('content')
  <section class="account-shell page-shell">
    <div class="account-intro"><div class="eyebrow">Studio access</div><h1 class="page-title">Product admin</h1><p>Sign in to manage products, inventory and customer orders.</p></div>
    <form class="account-form" method="POST" action="{{ route('admin.login.submit') }}">@csrf
      <h2>Administrator sign in</h2>
      <label class="field"><span>Email address</span><input name="email" type="email" autocomplete="username" required value="{{ old('email') }}"></label>
      <label class="field"><span>Password</span><input name="password" type="password" autocomplete="current-password" required></label>
      <button class="button full-width" type="submit">Sign in</button>
    </form>
  </section>
@endsection
