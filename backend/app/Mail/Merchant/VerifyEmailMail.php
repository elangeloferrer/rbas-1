<?php

namespace App\Mail\Merchant;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     *@paramstring  $verificationUrl  The signed URL the merchant clicks to verify.
     *@paramstring  $firstName        The merchant's first name for personalisation.
     */
    public function __construct(
        public readonly string $verificationUrl,
        public readonly string $firstName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Merchant Email Address',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.merchant.verify-email',
        );
    }
}
