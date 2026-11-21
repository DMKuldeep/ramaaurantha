<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        $products = Product::query()->whereIn('id', array_keys($cart))->get()->keyBy('id');
        $items = collect($cart)->map(function ($quantity, $id) use ($products): ?array {
            $product = $products->get($id);

            return $product ? ['product' => $product, 'quantity' => (int) $quantity] : null;
        })->filter()->values();
        $subtotal = $items->sum(fn (array $item): int => $item['product']->price * $item['quantity']);

        return view('store.cart', [
            'items' => $items,
            'subtotal' => $subtotal,
            'delivery' => $subtotal === 0 || $subtotal >= 1499 ? 0 : 99,
        ]);
    }

    public function add(Request $request, Product $product)
    {
        $quantity = max(1, min(99, (int) $request->input('quantity', 1)));
        $cart = $request->session()->get('cart', []);
        $nextQuantity = (int) ($cart[$product->id] ?? 0) + $quantity;

        if ($product->stock_quantity < $nextQuantity) {
            return back()->withErrors([
                'cart' => $product->stock_quantity > 0
                    ? "Only {$product->stock_quantity} unit(s) of {$product->name} are in stock."
                    : "{$product->name} is currently out of stock.",
            ]);
        }

        $cart[$product->id] = $nextQuantity;
        $request->session()->put('cart', $cart);

        return back()->with('success', $product->name.' added to your bag.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:99']]);
        $cart = $request->session()->get('cart', []);

        if ($data['quantity'] === 0) {
            unset($cart[$product->id]);
        } elseif ($data['quantity'] > $product->stock_quantity) {
            return back()->withErrors(['cart' => "Only {$product->stock_quantity} unit(s) of {$product->name} are in stock."]);
        } else {
            $cart[$product->id] = $data['quantity'];
        }

        $request->session()->put('cart', $cart);

        return back()->with('success', 'Your bag has been updated.');
    }

    public function remove(Request $request, Product $product)
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$product->id]);
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Item removed from your bag.');
    }
}
