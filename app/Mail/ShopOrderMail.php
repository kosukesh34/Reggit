<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** 配送注文の注文確認（confirmed）・発送（shipped）・返金（refunded）のお知らせ。 */
class ShopOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public const SUBJECTS = [
        'confirmed' => 'ご注文ありがとうございます',
        'shipped' => 'ご注文の商品を発送しました',
        'refunded' => 'ご注文の返金が完了しました',
    ];

    /**
     * @param  array<string, string>  $legal
     */
    public function __construct(
        public readonly Order $order,
        public readonly string $event,
        public readonly string $shopName,
        public readonly array $legal,
        public readonly string $receiptUrl,
        public readonly ?string $trackingUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '【'.$this->shopName.'】'.(self::SUBJECTS[$this->event] ?? 'ご注文について').'（注文番号 '.$this->order->formattedOrderNumber().'）');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.shop-order');
    }
}
