<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShopMagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $loginUrl,
        public readonly string $shopName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->shopName.'へのログインリンク');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.shop-magic-link');
    }
}
