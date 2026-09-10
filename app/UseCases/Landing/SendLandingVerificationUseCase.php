<?php

namespace App\UseCases\Landing;

use App\Models\LandingRegistration;
use App\Services\LandingRegistrationMailer;

class SendLandingVerificationUseCase
{
    public function __construct(
        private LandingRegistrationMailer $mailer,
    ) {}

    public function handle(string $email): string
    {
        $registration = LandingRegistration::query()->firstOrCreate(['email' => $email]);

        if ($registration->submitted_at !== null) {
            return 'このメールアドレスは登録済みです。';
        }

        if ($registration->email_verified_at !== null) {
            $this->mailer->sendFormUrl($registration);

            return '入力フォームのURLをメールで送信しました。メールをご確認ください。';
        }

        $this->mailer->sendVerification($registration);

        return '認証メールを送信しました。メール内のリンクを開いて認証を完了してください。';
    }
}
