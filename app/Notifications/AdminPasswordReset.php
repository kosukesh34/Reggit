<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AdminPasswordReset extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reggit 管理画面のパスワード再設定')
            ->line('管理画面のパスワード再設定を受け付けました。')
            ->action('パスワードを再設定', $this->resetUrl($notifiable))
            ->line('このリンクの有効期限は60分です。')
            ->line('お心当たりがない場合、このメールは破棄してください。');
    }

    protected function resetUrl($notifiable): string
    {
        return route('admin.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
    }
}
