@extends('layouts.store')

@section('title', 'Edit product | Rama Aurantha')
@section('content')
  <section class="page-shell admin-shell">
    <div class="admin-heading"><div><div class="eyebrow">Edit listing</div><h1 class="page-title">{{ $product->name }}</h1></div><a class="text-link" href="{{ route('admin.dashboard') }}">Back to admin</a></div>
    <form class="admin-form" method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
      @csrf @method('PUT')
      <label class="field"><span>Product code</span><input name="code" required maxlength="24" value="{{ old('code', $product->code) }}"></label>
      <label class="field"><span>Product name</span><input name="name" required maxlength="90" value="{{ old('name', $product->name) }}"></label>
      <div class="form-grid"><label class="field"><span>Collection</span><select name="category"><option value="urli" @selected($product->category === 'urli')>Urli candle</option><option value="soy" @selected($product->category === 'soy')>Soy wax candle</option></select></label><label class="field"><span>Price (₹)</span><input name="price" type="number" min="1" required value="{{ old('price', $product->price) }}"></label></div>
      <label class="field"><span>Available inventory (units)</span><input name="stock_quantity" type="number" min="0" max="1000000" required value="{{ old('stock_quantity', $product->stock_quantity) }}"></label>
      <label class="field"><span>Tags <small>Separate with commas</small></span><input name="tags" maxlength="120" value="{{ old('tags', implode(', ', $product->tags ?? [])) }}"></label>
      <div class="admin-image-preview"><img src="{{ $product->image }}" alt="{{ $product->name }}"></div>
      <label class="field"><span>Replace product image</span><input name="image_file" type="file" accept="image/png,image/jpeg,image/webp"></label>
      <label class="field"><span>Or use an HTTPS image URL</span><input name="image_url" type="url" placeholder="Leave blank to keep current image"></label>
      <button class="button" type="submit">Save changes</button>
    </form>
  </section>
@endsection
