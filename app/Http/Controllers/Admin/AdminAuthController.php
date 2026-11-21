<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function show()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:200'],
        ]);
        $credentials['email'] = strtolower($credentials['email']);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'The administrator email or password is incorrect.',
            ]);
        }
        $user = Auth::user();
        if (! $user || ! in_array($user->role, ['admin', 'owner'], true)) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'The administrator email or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'owner'], true), 403);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'You are signed out.');
    }
}
