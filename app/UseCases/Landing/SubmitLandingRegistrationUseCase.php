<?php

namespace App\UseCases\Landing;

use App\Enums\LandingInquiryPlan;
use App\Enums\ProjectPlan;
use App\Models\AdminUser;
use App\Models\LandingRegistration;
use App\Models\Project;
use App\Services\Admin\ProjectProvisioningService;
use App\Services\LandingRegistrationMailer;
use App\UseCases\Landing\Data\LandingRegistrationCompleteView;
use App\UseCases\Landing\Data\LandingRegistrationFormInput;
use App\UseCases\Landing\Data\LandingRegistrationStatusView;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SubmitLandingRegistrationUseCase
{
    public function __construct(
        private ProjectProvisioningService $projectProvisioningService,
        private LandingRegistrationMailer $mailer,
        private LandingRegistrationCompleteBuilder $completeBuilder,
    ) {}

    public function handle(LandingRegistrationFormInput $input): LandingRegistrationCompleteView|LandingRegistrationStatusView
    {
        $registration = $input->registration;
        if ($registration->submitted_at !== null) {
            return $this->completeBuilder->fromRegistration($registration, null);
        }

        $password = Str::random(12);
        $this->provision($registration, $input, $password);
        $view = $this->completeBuilder->fromRegistration($registration, $password);
        $this->sendReadyMail($registration->email, $view);

        return $view;
    }

    private function provision(
        LandingRegistration $registration,
        LandingRegistrationFormInput $input,
        string $password,
    ): void {
        $projectKey = $this->uniqueProjectKey();
        $loginId = $this->uniqueLoginId($registration->email);
        $storeName = $input->formPayload['store_name'];
        $liffUrl = url('/liff/'.$projectKey);

        DB::transaction(function () use ($registration, $input, $password, $projectKey, $loginId, $storeName, $liffUrl): void {
            $this->projectProvisioningService->createWithConfig(
                [
                    'project_key' => $projectKey,
                    'name' => $storeName,
                    'is_active' => true,
                    'plan' => $input->formPayload['plan'] === LandingInquiryPlan::NATIVE
                        ? ProjectPlan::PREMIUM
                        : ProjectPlan::NORMAL,
                ],
                $this->settingsPayload($storeName, $liffUrl),
                [
                    'login_id' => $loginId,
                    'email' => $registration->email,
                    'password' => $password,
                    'name' => $input->formPayload['contact_name'],
                ],
            );
            $project = Project::query()->where('project_key', $projectKey)->firstOrFail();
            $registration->fill($input->formPayload);
            $registration->project_id = $project->id;
            $registration->admin_login_id = $loginId;
            $registration->submitted_at = now();
            $registration->save();
        });
    }

    /**
     * @return array{
     *     line_liff_id: string,
     *     line_liff_url: string,
     *     frontend_header_text: string,
     *     business_hours_start: string,
     *     business_hours_end: string,
     *     line_primary_color: string,
     *     line_primary_dark: string,
     *     line_success_color: string,
     *     line_danger_color: string,
     * }
     */
    private function settingsPayload(string $storeName, string $liffUrl): array
    {
        $configuredLiffUrl = trim((string) config('line.liff_url', ''));

        return [
            'line_liff_id' => trim((string) config('line.liff_id', '')),
            'line_liff_url' => $configuredLiffUrl !== '' ? $configuredLiffUrl : $liffUrl,
            'frontend_header_text' => $storeName,
            'business_hours_start' => '10:00',
            'business_hours_end' => '22:00',
            'line_primary_color' => '#000000',
            'line_primary_dark' => '#333333',
            'line_success_color' => '#000000',
            'line_danger_color' => '#dc3545',
        ];
    }

    private function sendReadyMail(string $email, LandingRegistrationCompleteView $view): void
    {
        if ($view->password === null) {
            return;
        }
        try {
            $this->mailer->sendReady(
                $email,
                $view->storeName,
                $view->liffUrl,
                $view->loginUrl,
                $view->loginId,
                $view->password,
            );
        } catch (Throwable $e) {
            LogHelper::error('[LandingRegistration] ready mail failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function uniqueProjectKey(): string
    {
        do {
            $key = 'lp'.Str::lower(Str::random(8));
        } while (Project::query()->where('project_key', $key)->exists());

        return $key;
    }

    private function uniqueLoginId(string $email): string
    {
        $local = preg_replace('/[^a-zA-Z0-9_-]/', '', Str::before($email, '@'));
        $base = is_string($local) && $local !== '' ? Str::lower(substr($local, 0, 80)) : 'admin';
        $candidate = $base;
        $suffix = 1;
        while (AdminUser::query()->where('login_id', $candidate)->exists()) {
            $candidate = $base.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
