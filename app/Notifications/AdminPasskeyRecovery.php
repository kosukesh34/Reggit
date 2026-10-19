<?php

namespace App\Notifications;

use App\Models\AdminUser;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class AdminPasskeyRecovery extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reggit 管理画面のパスキー再登録')
            ->line('パスキーが使えない方向けの再登録を受け付けました。')
            ->action('パスキーを再登録する', $this->recoveryUrl($notifiable))
            ->line('このリンクの有効期限は60分です。開くと登録済みのパスキーは無効になり、新しいパスキーの登録が必要になります。')
            ->line('お心当たりがない場合、このメールは破棄してください。');
    }

    public function recoveryUrl(object $notifiable): string
    {
        /** @var AdminUser $notifiable */
        return URL::temporarySignedRoute(
            'admin.passkey.recovery.confirm',
            now()->addMinutes(60),
            [
                'admin' => $notifiable->getAuthIdentifier(),
                'hash' => sha1((string) $notifiable->getEmailForPasswordReset()),
            ],
        );
    }
}
