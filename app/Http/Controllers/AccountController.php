<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request)
    {
        return view('account.show', [
            'user' => $request->user(),
            'addresses' => $request->user()->addresses()->latest()->get(),
            'orders' => $request->user()->orders()->latest()->limit(10)->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'regex:/^[0-9+][0-9+ ]{7,23}$/'],
        ]);
        $request->user()->update($data);

        return back()->with('success', 'Your profile has been updated.');
    }

    public function storeAddress(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^[0-9+][0-9+ ]{7,23}$/'],
            'address' => ['required', 'string', 'max:500'],
            'landmark' => ['nullable', 'string', 'max:160'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'regex:/^[0-9]{6}$/'],
        ]);
        $request->user()->addresses()->create($data);

        return back()->with('success', 'Delivery address saved.');
    }

    public function deleteAddress(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $address->delete();

        return back()->with('success', 'Delivery address removed.');
    }
}
