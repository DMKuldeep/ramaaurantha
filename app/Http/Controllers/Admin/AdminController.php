<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'products' => Product::query()->orderBy('created_at')->orderBy('code')->get(),
            'orders' => Order::query()->with('items')->latest()->paginate(20),
        ]);
    }

    public function updateOrder(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])],
        ]);

        DB::transaction(function () use ($data, $order): void {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->inventory_released_at && $data['status'] !== 'cancelled') {
                throw ValidationException::withMessages(['status' => 'A cancelled order cannot be reopened.']);
            }
            if ($order->status === 'delivered' && $data['status'] === 'cancelled') {
                throw ValidationException::withMessages(['status' => 'A delivered order cannot be cancelled.']);
            }
            if ($data['status'] === 'cancelled' && ! $order->inventory_released_at) {
                foreach ($order->items()->get() as $item) {
                    if ($item->product_id) {
                        Product::query()->whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
                    }
                }
                $order->inventory_released_at = now();
            }
            $order->status = $data['status'];
            $order->save();
        });

        return back()->with('success', 'Order status updated.');
    }

    public function storeAdmin(Request $request)
    {
        abort_unless($request->user()->role === 'owner', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:200'],
        ]);
        User::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        return back()->with('success', 'Administrator account created.');
    }
}
