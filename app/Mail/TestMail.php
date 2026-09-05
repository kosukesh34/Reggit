<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $bodyMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reggit メール送信テスト',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test',
        );
    }
}
