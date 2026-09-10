<?php

namespace App\UseCases\Landing;

use App\Models\LandingRegistration;
use App\Models\Project;
use App\UseCases\Landing\Data\LandingRegistrationCompleteView;
use App\UseCases\Landing\Data\LandingRegistrationStatusView;

class LandingRegistrationCompleteBuilder
{
    public function fromRegistration(
        LandingRegistration $registration,
        ?string $password,
    ): LandingRegistrationCompleteView|LandingRegistrationStatusView {
        $project = Project::query()->find($registration->project_id);
        $loginId = $registration->admin_login_id;
        if ($project === null || $loginId === null) {
            return new LandingRegistrationStatusView(
                '登録済みです',
                'この入力フォームは送信済みです。',
                null,
                null,
            );
        }

        return new LandingRegistrationCompleteView(
            '店舗の準備ができました',
            $password !== null
                ? '管理画面のログイン情報と店舗アプリのURLです。パスワードはこの画面とメールにだけ表示されます。'
                : 'パスワードは登録時にお送りしたメールをご確認ください。',
            (string) ($registration->store_name ?? $project->name),
            url('/liff/'.$project->project_key),
            url('/admin/'.$project->project_key.'/login'),
            $loginId,
            $password,
        );
    }
}
