<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeatherCouponApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $storeName,
        public readonly string $couponName,
        public readonly int $maxProbability,
        public readonly string $expiresAt,
        public readonly string $approvalUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "【承認のお願い】{$this->storeName} の雨の日クーポン");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.weather-coupon-approval');
    }
}
