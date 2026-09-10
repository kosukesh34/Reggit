<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LandingReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $storeName,
        public readonly string $liffUrl,
        public readonly string $loginUrl,
        public readonly string $loginId,
        public readonly string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【Reggit】店舗の準備ができました',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.landing-ready',
        );
    }
}
