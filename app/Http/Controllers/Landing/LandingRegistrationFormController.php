<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\LandingRegistrationFormRequest;
use App\Models\LandingRegistration;
use App\UseCases\Landing\Data\LandingRegistrationCompleteView;
use App\UseCases\Landing\Data\LandingRegistrationFormInput;
use App\UseCases\Landing\Data\LandingRegistrationFormView;
use App\UseCases\Landing\Data\LandingRegistrationStatusView;
use App\UseCases\Landing\ShowLandingRegistrationFormUseCase;
use App\UseCases\Landing\SubmitLandingRegistrationUseCase;
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

        return $this->render($this->submitLandingRegistrationUseCase->handle(
            new LandingRegistrationFormInput($registration, $request->toFormPayload()),
        ));
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

    private function ensureVerified(LandingRegistration $registration): void
    {
        if ($registration->email_verified_at === null) {
            abort(403);
        }
    }
}
