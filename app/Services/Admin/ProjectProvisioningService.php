<?php

namespace App\Services\Admin;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\AdminUser;
use App\Models\Project;
use App\Models\Settings\ProjectProfileSmaregiSetting;
use App\Models\SmaregiContract;
use App\Services\Line\LineLiffAdminService;
use App\Util\StrHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectProvisioningService
{
    public function __construct(
        private readonly ShopSettingRepository $shopSettingRepository,
        private readonly LineLiffAdminService $lineLiffAdminService,
        private readonly PublicImageStorageService $publicImageStorage,
    ) {}

    /**
     * @param  array{
     *     project_key: string,
     *     name: string,
     *     description?: string|null,
     *     is_active?: bool,
     *     plan?: string,
     * }  $projectPayload
     * @param  array{
     *     line_liff_id?: string,
     *     line_liff_url?: string,
     *     frontend_header_text?: string,
     *     business_hours_start?: string,
     *     business_hours_end?: string,
     *     line_primary_color?: string,
     *     line_primary_dark?: string,
     *     line_success_color?: string,
     *     line_danger_color?: string,
     *     smaregi_contract_id?: string|null,
     *     smaregi_client_id?: string|null,
     *     smaregi_client_secret?: string|null,
     * }  $settingsPayload
     * @param  array{
     *     login_id: string,
     *     email: string,
     *     password: string,
     *     name: string,
     * }|null  $adminPayload
     * @return array{project: Project, admin: AdminUser|null}
     */
    public function createWithConfig(array $projectPayload, array $settingsPayload, ?array $adminPayload = null): array
    {
        return DB::transaction(function () use ($projectPayload, $settingsPayload, $adminPayload) {
            $project = Project::create([
                'project_key' => $projectPayload['project_key'],
                'name' => $projectPayload['name'],
                'description' => $projectPayload['description'] ?? null,
                'is_active' => (bool) ($projectPayload['is_active'] ?? true),
                'plan' => $projectPayload['plan'] ?? 'normal',
            ]);

            $this->syncProjectSettings((int) $project->id, $settingsPayload);
            $this->syncSmaregiSettings((int) $project->id, $settingsPayload);

            $admin = null;
            if ($adminPayload !== null) {
                $admin = $this->createProjectAdmin((int) $project->id, $adminPayload);
            }

            return ['project' => $project, 'admin' => $admin];
        });
    }

    /**
     * @param  array{
     *     line_liff_id?: string,
     *     line_liff_url?: string,
     *     frontend_header_text?: string,
     *     business_hours_start?: string,
     *     business_hours_end?: string,
     *     line_primary_color?: string,
     *     line_primary_dark?: string,
     *     line_success_color?: string,
     *     line_danger_color?: string,
     *     smaregi_contract_id?: string|null,
     *     smaregi_client_id?: string|null,
     *     smaregi_client_secret?: string|null,
     * }  $settingsPayload
     */
    public function syncProjectSettings(int $projectId, array $settingsPayload): void
    {
        $liffId = (string) ($settingsPayload['line_liff_id'] ?? '');
        $this->shopSettingRepository->setLineLiffId($liffId, $projectId);
        $this->shopSettingRepository->setLineLiffUrl((string) ($settingsPayload['line_liff_url'] ?? ''), $projectId);
        $this->shopSettingRepository->setValue('frontend_header_text', (string) ($settingsPayload['frontend_header_text'] ?? ''), 'string', $projectId);
        $this->shopSettingRepository->setValue('business_hours_start', (string) ($settingsPayload['business_hours_start'] ?? '10:00'), 'time', $projectId);
        $this->shopSettingRepository->setValue('business_hours_end', (string) ($settingsPayload['business_hours_end'] ?? '22:00'), 'time', $projectId);
        $this->shopSettingRepository->setValue('line_primary_color', (string) ($settingsPayload['line_primary_color'] ?? '#000000'), 'string', $projectId);
        $this->shopSettingRepository->setValue('line_primary_dark', (string) ($settingsPayload['line_primary_dark'] ?? '#333333'), 'string', $projectId);
        $this->shopSettingRepository->setValue('line_success_color', (string) ($settingsPayload['line_success_color'] ?? '#000000'), 'string', $projectId);
        $this->shopSettingRepository->setValue('line_danger_color', (string) ($settingsPayload['line_danger_color'] ?? '#dc3545'), 'string', $projectId);

        if (config('line.auto_enable_liff_module_mode', true) && trim($liffId) !== '') {
            $this->lineLiffAdminService->hideActionMenu($liffId);
        }
    }

    public function syncFrontendLogo(int $projectId, ?UploadedFile $file, bool $clear): void
    {
        if ($file !== null) {
            $logoUrl = $this->publicImageStorage->store(
                $file,
                $projectId,
                PublicImageStorageService::DIR_FRONTEND_LOGO,
            );
            $this->shopSettingRepository->setValue('frontend_logo_url', $logoUrl, 'string', $projectId);

            return;
        }

        if ($clear) {
            $this->shopSettingRepository->setValue('frontend_logo_url', '', 'string', $projectId);
        }
    }

    /**
     * @param  array{
     *     smaregi_contract_id?: string|null,
     *     smaregi_client_id?: string|null,
     *     smaregi_client_secret?: string|null,
     * }  $settingsPayload
     */
    public function syncSmaregiSettings(int $projectId, array $settingsPayload): void
    {
        if (array_key_exists('smaregi_client_id', $settingsPayload)) {
            $clientId = $settingsPayload['smaregi_client_id'];
            $this->shopSettingRepository->setValue(
                'smaregi_client_id',
                is_string($clientId) ? trim($clientId) : '',
                'string',
                $projectId
            );
        }

        if (array_key_exists('smaregi_client_secret', $settingsPayload)) {
            $clientSecret = $settingsPayload['smaregi_client_secret'];
            if (is_string($clientSecret) && trim($clientSecret) !== '') {
                $this->shopSettingRepository->setValue('smaregi_client_secret', trim($clientSecret), 'string', $projectId);
            }
        }

        if (! array_key_exists('smaregi_contract_id', $settingsPayload)) {
            return;
        }

        $this->linkContract($projectId, $settingsPayload['smaregi_contract_id'] ?? null);
    }

    public function linkContract(int $projectId, ?string $contractId): void
    {
        $trimmed = $contractId !== null ? trim($contractId) : '';

        if (StrHelper::isEmptyStringOrNull($trimmed)) {
            $this->shopSettingRepository->setValue('smaregi_contract_id', '', 'string', $projectId);
            SmaregiContract::where('project_id', $projectId)->update(['project_id' => null]);

            return;
        }

        $linkedToAnotherProject = ProjectProfileSmaregiSetting::query()
            ->where('smaregi_contract_id', $trimmed)
            ->where('project_id', '!=', $projectId)
            ->exists()
            || SmaregiContract::query()
                ->where('contract_id', $trimmed)
                ->whereNotNull('project_id')
                ->where('project_id', '!=', $projectId)
                ->exists();

        if ($linkedToAnotherProject) {
            throw ValidationException::withMessages([
                'smaregi_contract_id' => 'このスマレジ契約は別の店舗に紐付いています。',
            ]);
        }

        $this->shopSettingRepository->setValue('smaregi_contract_id', $trimmed, 'string', $projectId);
        SmaregiContract::where('project_id', $projectId)->where('contract_id', '!=', $trimmed)->update(['project_id' => null]);
        SmaregiContract::where('contract_id', $trimmed)->update(['project_id' => $projectId]);
    }

    /**
     * @param  array{
     *     login_id: string,
     *     email: string,
     *     password: string,
     *     name: string,
     * }  $adminPayload
     */
    private function createProjectAdmin(int $projectId, array $adminPayload): AdminUser
    {
        $admin = AdminUser::create([
            'login_id' => $adminPayload['login_id'],
            'email' => $adminPayload['email'],
            'password' => $adminPayload['password'],
            'name' => $adminPayload['name'],
            'role' => AdminUser::ROLE_PROJECT_ADMIN,
            'project_id' => $projectId,
            'created_by_id' => null,
        ]);
        $admin->projects()->sync([$projectId]);

        return $admin;
    }
}
