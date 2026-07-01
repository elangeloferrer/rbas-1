<?php

namespace App\Mail\Merchant;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     *@paramstring  $resetUrl  The full URL the merchant clicks to reset their password.
     *@paramstring  $firstName The merchant's first name for personalisation.
     */
    public function __construct(
        public readonly string $resetUrl,
        public readonly string $firstName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Merchant Password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.merchant.password-reset',
        );
    }
}
