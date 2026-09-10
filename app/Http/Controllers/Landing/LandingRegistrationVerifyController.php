<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingRegistration;
use App\UseCases\Landing\Data\LandingRegistrationStatusView;
use App\UseCases\Landing\VerifyLandingEmailUseCase;
use App\Util\LogHelper;
use Throwable;

class LandingRegistrationVerifyController extends Controller
{
    public function __construct(
        private VerifyLandingEmailUseCase $verifyLandingEmailUseCase,
    ) {}

    public function __invoke(LandingRegistration $registration)
    {
        try {
            $view = $this->verifyLandingEmailUseCase->handle($registration);
        } catch (Throwable $e) {
            LogHelper::error('[LandingRegistration] form url mail failed', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            return view('landing.status', [
                'view' => new LandingRegistrationStatusView(
                    '認証は完了しました',
                    '入力フォームの案内メールを送れませんでした。トップの登録欄から同じメールアドレスを再送信してください。',
                    '/',
                    'トップへ戻る',
                ),
            ]);
        }

        return view('landing.status', ['view' => $view]);
    }
}
