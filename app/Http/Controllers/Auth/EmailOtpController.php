<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailOtpController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);
        if (config('mail.default') === 'smtp' && blank(config('mail.mailers.smtp.host'))) {
            throw ValidationException::withMessages([
                'email' => 'Email delivery is not configured. Set MAIL_HOST in your private .env first.',
            ]);
        }
        $email = Str::lower(trim($data['email']));
        $user = User::query()->where('email', $email)->first();

        if ($user?->role !== 'admin' && $user?->role !== 'owner') {
            $code = (string) random_int(100000, 999999);
            EmailOtp::query()->updateOrCreate(
                ['email' => $email],
                [
                    'code_hash' => Hash::make($code),
                    'expires_at' => now()->addMinutes(config('store.otp_expiry_minutes')),
                    'attempts' => 0,
                    'used_at' => null,
                ],
            );
            if (config('mail.default') === 'log' && config('app.env') === 'local') {
                Log::info('Local customer sign-in OTP (enter this 6-digit code)', [
                    'email' => $email,
                    'otp' => $code,
                ]);
            } else {
                try {
                    Mail::to($email)->send(new LoginOtpMail($code));
                } catch (TransportExceptionInterface $exception) {
                    Log::error('Could not send customer login OTP.', ['exception' => $exception]);

                    throw ValidationException::withMessages([
                        'email' => 'We could not send your sign-in code. Check the Gmail App Password in your .env and try again.',
                    ]);
                }
            }
        }

        $request->session()->put('otp_email', $email);
        $request->session()->put('otp_name', trim((string) ($data['name'] ?? '')));

        $message = config('mail.default') === 'log'
            ? 'Local email mode is on: no email was sent. Check storage/logs/laravel.log for the sign-in code.'
            : 'If this address can sign in as a customer, a verification code has been emailed.';

        return back()->with('otp_sent', $message);
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = Str::lower(trim($data['email']));
        $valid = DB::transaction(function () use ($email, $data): bool {
            $otp = EmailOtp::query()->where('email', $email)->lockForUpdate()->first();
            if (! $otp || $otp->used_at || $otp->expires_at->isPast() || $otp->attempts >= 5) {
                return false;
            }

            if (! Hash::check($data['code'], $otp->code_hash)) {
                $otp->increment('attempts');

                return false;
            }

            $otp->update(['used_at' => now()]);

            return true;
        });

        $user = User::query()->where('email', $email)->first();
        if (! $valid || in_array($user?->role, ['admin', 'owner'], true)) {
            throw ValidationException::withMessages([
                'code' => 'The code is invalid or expired. Request a new one and try again.',
            ]);
        }

        $user ??= User::query()->create([
            'name' => $request->session()->get('otp_name') ?: Str::before($email, '@'),
            'email' => $email,
            'email_verified_at' => now(),
            'role' => 'customer',
        ]);
        $request->session()->forget(['otp_email', 'otp_name']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('account'))->with('success', 'You are signed in.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('shop')->with('success', 'You are signed out.');
    }
}
