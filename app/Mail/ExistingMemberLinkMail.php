<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExistingMemberLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $confirmUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '既存会員連携の確認');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.existing-member-link');
    }
}
