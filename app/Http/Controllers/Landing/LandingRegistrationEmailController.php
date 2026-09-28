<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\LandingRegistrationEmailRequest;
use App\UseCases\Landing\SendLandingVerificationUseCase;
use App\Util\LogHelper;
use Throwable;

class LandingRegistrationEmailController extends Controller
{
    public function __construct(
        private SendLandingVerificationUseCase $sendLandingVerificationUseCase,
    ) {}

    public function __invoke(LandingRegistrationEmailRequest $request)
    {
        try {
            $message = $this->sendLandingVerificationUseCase->handle($request->emailAddress());
        } catch (Throwable $e) {
            LogHelper::error('[LandingRegistration] verification mail failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('landing.register.email.show')
                ->withInput()
                ->withErrors(['email' => 'メールを送信できませんでした。しばらくしてから再度お試しください。']);
        }

        return redirect()->route('landing.register.email.show')->with('status', $message);
    }
}
