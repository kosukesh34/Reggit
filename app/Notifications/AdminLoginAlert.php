<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminLoginAlert extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ?string $ip,
        public readonly string $userAgent,
        public readonly string $occurredAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ip = ($this->ip !== null && $this->ip !== '') ? $this->ip : '不明';

        return (new MailMessage)
            ->subject('Reggit 管理画面へのログイン通知')
            ->line('管理画面へのログインがありました。')
            ->line('日時: '.$this->occurredAt)
            ->line('IPアドレス: '.$ip)
            ->line('端末情報: '.$this->userAgent)
            ->line('お心当たりがない場合は、ただちにパスワードを変更し、パスキーを見直してください。');
    }
}
