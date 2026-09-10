<?php

namespace App\UseCases\Landing;

use App\Models\LandingRegistration;
use App\Services\LandingRegistrationMailer;
use App\UseCases\Landing\Data\LandingRegistrationStatusView;

class VerifyLandingEmailUseCase
{
    public function __construct(
        private LandingRegistrationMailer $mailer,
    ) {}

    public function handle(LandingRegistration $registration): LandingRegistrationStatusView
    {
        if ($registration->submitted_at !== null) {
            return new LandingRegistrationStatusView(
                '登録済みです',
                'このメールアドレスの入力フォームは送信済みです。',
                null,
                null,
            );
        }

        if ($registration->email_verified_at === null) {
            $registration->email_verified_at = now();
            $registration->save();
        }

        $formUrl = $this->mailer->sendFormUrl($registration);

        return new LandingRegistrationStatusView(
            '認証が完了しました',
            '入力フォームのURLをメールで送信しました。メールをご確認ください。',
            $formUrl,
            '入力フォームへ進む',
        );
    }
}
