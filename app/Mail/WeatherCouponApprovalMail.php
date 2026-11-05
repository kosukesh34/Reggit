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
        /** 「雨の日」「気温」 */
        public readonly string $typeLabel,
        public readonly string $couponName,
        /** 「最大降水確率 80%」「最高気温 31.5℃」 */
        public readonly string $forecast,
        public readonly string $expiresAt,
        public readonly string $approvalUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "【承認のお願い】{$this->storeName} の{$this->typeLabel}の配信");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.weather-coupon-approval');
    }
}
