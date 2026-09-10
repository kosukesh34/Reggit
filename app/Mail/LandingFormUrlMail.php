<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LandingFormUrlMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $formUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【Reggit】入力フォームのご案内',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.landing-form-url',
        );
    }
}
