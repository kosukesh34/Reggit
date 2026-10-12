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

        $plan = $registration->plan;
        if ($plan === null || $plan === '') {
            $plan = LandingInquiryPlan::LINE;
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
            $plan,
            (string) ($registration->message ?? ''),
            $this->planOptions(),
        );
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    private function planOptions(): array
    {
        $options = [];
        foreach (LandingInquiryPlan::getValues() as $value => $label) {
            $options[] = [
                'value' => (string) $value,
                'label' => (string) $label,
                'description' => $this->planDescription((string) $value),
            ];
        }

        return $options;
    }

    private function planDescription(string $value): string
    {
        return match ($value) {
            LandingInquiryPlan::LINE => 'LINEミニアプリで会員証・注文・予約・順番待ちを利用します。',
            LandingInquiryPlan::NATIVE => 'LINEに加えて、iOS / Android の専用アプリも利用します。',
            LandingInquiryPlan::UNDECIDED => 'まずは LINE プランで発行します。あとから変更できます。',
            default => '',
        };
    }
}
