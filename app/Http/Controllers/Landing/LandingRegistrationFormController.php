<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\LandingRegistrationFormRequest;
use App\Models\AdminUser;
use App\Models\LandingRegistration;
use App\UseCases\Landing\Data\LandingRegistrationCompleteView;
use App\UseCases\Landing\Data\LandingRegistrationFormInput;
use App\UseCases\Landing\Data\LandingRegistrationFormView;
use App\UseCases\Landing\Data\LandingRegistrationStatusView;
use App\UseCases\Landing\ShowLandingRegistrationFormUseCase;
use App\UseCases\Landing\SubmitLandingRegistrationUseCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandingRegistrationFormController extends Controller
{
    public function __construct(
        private ShowLandingRegistrationFormUseCase $showLandingRegistrationFormUseCase,
        private SubmitLandingRegistrationUseCase $submitLandingRegistrationUseCase,
    ) {}

    public function show(LandingRegistration $registration): View
    {
        $this->ensureVerified($registration);

        return $this->render($this->showLandingRegistrationFormUseCase->handle($registration));
    }

    public function store(LandingRegistrationFormRequest $request, LandingRegistration $registration): View
    {
        $this->ensureVerified($registration);

        $result = $this->submitLandingRegistrationUseCase->handle(
            new LandingRegistrationFormInput($registration, $request->toFormPayload()),
        );
        $this->loginIssuedAdmin($result);

        return $this->render($result);
    }

    private function render(
        LandingRegistrationFormView|LandingRegistrationCompleteView|LandingRegistrationStatusView $result,
    ): View {
        if ($result instanceof LandingRegistrationFormView) {
            return view('landing.form', ['view' => $result]);
        }
        if ($result instanceof LandingRegistrationCompleteView) {
            return view('landing.complete', ['view' => $result]);
        }

        return view('landing.status', ['view' => $result]);
    }

    private function loginIssuedAdmin(
        LandingRegistrationFormView|LandingRegistrationCompleteView|LandingRegistrationStatusView $result,
    ): void {
        if (! $result instanceof LandingRegistrationCompleteView || $result->password === null) {
            return;
        }

        $admin = AdminUser::query()->where('login_id', $result->loginId)->first();
        if ($admin !== null) {
            Auth::guard('admin')->login($admin);
            session()->regenerate();
            session()->put('admin_login_via', 'admin');
            session()->put('password_hash_admin', Auth::guard('admin')->hashPasswordForCookie($admin->getAuthPassword()));
        }
    }

    private function ensureVerified(LandingRegistration $registration): void
    {
        if ($registration->email_verified_at === null) {
            abort(403);
        }
    }
}
