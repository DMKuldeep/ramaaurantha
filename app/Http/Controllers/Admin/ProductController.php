<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.dashboard');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['id'] = Str::slug($data['code']).'-'.Str::lower(Str::random(6));
        $data['tags'] = $this->tags($data['tags'] ?? '');
        $data['image_url'] = $this->imagePath($request, $data['image_url'] ?? null);
        unset($data['tags_text'], $data['image_file']);
        Product::query()->create($data);

        return redirect()->route('admin.dashboard')->with('success', 'Product added to your store.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);
        $data['tags'] = $this->tags($data['tags'] ?? '');
        $data['image_url'] = $this->imagePath($request, $data['image_url'] ?? null, $product);
        unset($data['tags_text'], $data['image_file']);
        $product->update($data);

        return redirect()->route('admin.dashboard')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        abort_unless(auth()->user()->role === 'owner', 403);
        $product->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Product removed from the catalogue.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:24', Rule::unique('products', 'code')->ignore($product?->id, 'id')],
            'name' => ['required', 'string', 'max:90'],
            'category' => ['required', Rule::in(['urli', 'soy'])],
            'price' => ['required', 'integer', 'min:1', 'max:10000000'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'tags' => ['nullable', 'string', 'max:120'],
            'image_url' => ['nullable', 'url:https', 'max:2000'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);
    }

    private function tags(?string $tags): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $tags))));
    }

    private function imagePath(Request $request, ?string $imageUrl, ?Product $product = null): string
    {
        if ($request->hasFile('image_file')) {
            if ($product?->image_url && str_starts_with($product->image_url, 'products/')) {
                Storage::disk('public')->delete($product->image_url);
            }

            return $request->file('image_file')->store('products', 'public');
        }

        return $imageUrl ?: ($product?->image_url ?? throw ValidationException::withMessages([
            'image_file' => 'Upload a product image or enter an HTTPS image URL.',
        ]));
    }
}
