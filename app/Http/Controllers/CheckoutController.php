<?php

namespace App\Http\Controllers;

use App\Mail\OrderPlacedMail;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function show(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        abort_if($cart === [], 404, 'Your bag is empty.');

        return view('store.checkout', [
            'items' => $this->cartItems($cart),
            'addresses' => $request->user()->addresses()->latest()->get(),
            'defaultAddress' => $request->user()->addresses()->latest()->first(),
        ]);
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^[0-9+][0-9+ ]{7,23}$/'],
            'address' => ['required', 'string', 'max:500'],
            'landmark' => ['nullable', 'string', 'max:160'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'regex:/^[0-9]{6}$/'],
            'note' => ['nullable', 'string', 'max:1000'],
            'save_address' => ['nullable', 'boolean'],
        ]);
        $cart = $request->session()->get('cart', []);
        if ($cart === [] || count($cart) > 50) {
            throw ValidationException::withMessages(['cart' => 'Your bag is empty or contains too many products.']);
        }

        $order = DB::transaction(function () use ($cart, $data, $request): Order {
            $products = Product::query()
                ->whereIn('id', array_keys($cart))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($products->count() !== count($cart)) {
                throw ValidationException::withMessages(['cart' => 'A product in your bag is no longer available. Refresh and try again.']);
            }

            $subtotal = 0;
            foreach ($cart as $id => $quantity) {
                $product = $products->get($id);
                $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
                if ($quantity === false || $quantity < 1 || $quantity > 99) {
                    throw ValidationException::withMessages(['cart' => 'Your bag contains an invalid quantity.']);
                }
                if ($product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Only {$product->stock_quantity} unit(s) of {$product->name} are currently in stock.",
                    ]);
                }
                $subtotal += $product->price * $quantity;
            }
            $delivery = $subtotal === 0 || $subtotal >= 1499 ? 0 : 99;
            $order = Order::query()->create([
                'order_number' => 'RA-'.strtoupper(Str::random(12)),
                'user_id' => $request->user()->id,
                'customer_name' => $data['name'],
                'email' => $request->user()->email,
                'phone' => $data['phone'],
                'address' => $data['address'],
                'landmark' => $data['landmark'] ?? null,
                'city' => $data['city'],
                'state' => $data['state'],
                'pincode' => $data['pincode'],
                'note' => $data['note'] ?? null,
                'subtotal' => $subtotal,
                'delivery' => $delivery,
                'total' => $subtotal + $delivery,
                'payment_method' => 'COD',
                'status' => 'placed',
            ]);

            foreach ($cart as $id => $quantity) {
                $product = $products->get($id);
                $product->decrement('stock_quantity', (int) $quantity);
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => (int) $quantity,
                ]);
            }

            if ($request->boolean('save_address')) {
                $request->user()->addresses()->firstOrCreate([
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'pincode' => $data['pincode'],
                ], [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'landmark' => $data['landmark'] ?? null,
                ]);
            }

            return $order->load('items');
        });

        $request->session()->forget('cart');
        Mail::to($order->email)->queue(new OrderPlacedMail($order));
        if (filter_var(config('store.admin_email'), FILTER_VALIDATE_EMAIL)) {
            Mail::to(config('store.admin_email'))->queue(new OrderPlacedMail($order));
        }

        return redirect()->route('orders.show', $order)->with('success', 'Your order request has been received.');
    }

    public function confirmation(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id || in_array($request->user()->role, ['admin', 'owner'], true), 404);

        return view('store.confirmation', ['order' => $order->load('items')]);
    }

    private function cartItems(array $cart)
    {
        $products = Product::query()->whereIn('id', array_keys($cart))->get()->keyBy('id');

        return collect($cart)->map(function ($quantity, $id) use ($products): ?array {
            $product = $products->get($id);

            return $product ? ['product' => $product, 'quantity' => (int) $quantity] : null;
        })->filter()->values();
    }
}
