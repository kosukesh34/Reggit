<?php

namespace App\UseCases\Onboarding;

use App\Models\AdminUser;
use App\Services\Admin\ProjectProvisioningService;
use App\UseCases\Onboarding\Data\OnboardingStoreInput;
use App\UseCases\Onboarding\Data\OnboardingStoreOutput;
use RuntimeException;

class OnboardingStoreUseCase
{
    public function __construct(
        private readonly ProjectProvisioningService $projectProvisioningService,
    ) {}

    public function handle(OnboardingStoreInput $input): OnboardingStoreOutput
    {
        $result = $this->projectProvisioningService->createWithConfig(
            $input->projectPayload,
            $input->settingsPayload,
            $input->adminPayload,
        );

        $admin = $result['admin'];
        if (! $admin instanceof AdminUser) {
            throw new RuntimeException('オンボーディングで管理者アカウントの作成に失敗しました。');
        }

        $project = $result['project'];

        return new OnboardingStoreOutput(
            [
                'id' => (int) $project->id,
                'project_key' => (string) $project->project_key,
                'name' => (string) $project->name,
            ],
            [
                'id' => (int) $admin->id,
                'login_id' => (string) $admin->login_id,
                'email' => (string) $admin->email,
            ],
            route('admin.project.login', ['project_key' => $project->project_key], false),
        );
    }
}
