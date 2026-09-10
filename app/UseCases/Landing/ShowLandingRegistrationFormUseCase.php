<?php

namespace App\UseCases\Landing;

use App\Enums\LandingInquiryPlan;
use App\Models\LandingRegistration;
use App\UseCases\Landing\Data\LandingRegistrationCompleteView;
use App\UseCases\Landing\Data\LandingRegistrationFormView;
use App\UseCases\Landing\Data\LandingRegistrationStatusView;
use Illuminate\Support\Facades\URL;

class ShowLandingRegistrationFormUseCase
{
    public function __construct(
        private LandingRegistrationCompleteBuilder $completeBuilder,
    ) {}

    public function handle(
        LandingRegistration $registration,
    ): LandingRegistrationFormView|LandingRegistrationCompleteView|LandingRegistrationStatusView {
        if ($registration->submitted_at !== null) {
            return $this->completeBuilder->fromRegistration($registration, null);
        }

        return new LandingRegistrationFormView(
            URL::temporarySignedRoute(
                'landing.register.form.submit',
                now()->addDays(LandingRegistration::FORM_EXPIRES_DAYS),
                ['registration' => $registration->id],
            ),
            $registration->email,
            (string) ($registration->store_name ?? ''),
            (string) ($registration->contact_name ?? ''),
            (string) ($registration->phone ?? ''),
            (string) ($registration->plan ?? ''),
            (string) ($registration->message ?? ''),
            $this->planOptions(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function planOptions(): array
    {
        $options = [];
        foreach (LandingInquiryPlan::getValues() as $value => $label) {
            $options[] = ['value' => (string) $value, 'label' => (string) $label];
        }

        return $options;
    }
}
