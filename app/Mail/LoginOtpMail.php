<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Rama Aurantha sign-in code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.login-otp',
            with: ['code' => $this->code, 'minutes' => (int) config('store.otp_expiry_minutes')],
        );
    }
}
