<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Services\Admin\PublicImageStorageService;
use Illuminate\Http\UploadedFile;

class UpdateFeatureSettingsUseCase
{
    public function __construct(
        private FeatureSettings $shopSettingRepository,
        private PublicImageStorageService $publicImageStorage,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(
        int $projectId,
        array $payload,
        ?UploadedFile $frontendLogoFile = null,
        bool $clearFrontendLogo = false,
        ?UploadedFile $stampDefaultImageFile = null,
        bool $clearStampDefaultImage = false,
    ): void {
        $features = $this->shopSettingRepository->withPreservedLotteryManagedFlags($payload['features'] ?? [], $projectId);
        $this->shopSettingRepository->setFeatureFlags($features, $projectId);
        $this->syncUseStampsFromFeatureFlag($projectId);

        $this->shopSettingRepository->setValue('admin_sidebar_color', $payload['admin_sidebar_color'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('admin_header_color', $payload['admin_header_color'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('admin_primary_color', $payload['admin_primary_color'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('frontend_header_text', $payload['frontend_header_text'] ?? '', 'string', $projectId);

        if ($stampDefaultImageFile !== null) {
            $this->shopSettingRepository->setValue(
                'stamp_default_image_url',
                $this->publicImageStorage->store(
                    $stampDefaultImageFile,
                    $projectId,
                    PublicImageStorageService::DIR_STAMP_DEFAULT,
                ),
                'string',
                $projectId,
            );
        } elseif ($clearStampDefaultImage) {
            $this->shopSettingRepository->setValue('stamp_default_image_url', '', 'string', $projectId);
        }

        if ($frontendLogoFile !== null) {
            $logoUrl = $this->publicImageStorage->store(
                $frontendLogoFile,
                $projectId,
                PublicImageStorageService::DIR_FRONTEND_LOGO,
            );
            $this->shopSettingRepository->setValue('frontend_logo_url', $logoUrl, 'string', $projectId);
        } elseif ($clearFrontendLogo) {
            $this->shopSettingRepository->setValue('frontend_logo_url', '', 'string', $projectId);
        }
    }

    private function syncUseStampsFromFeatureFlag(int $projectId): void
    {
        $useStamps = $this->shopSettingRepository->isFeatureEnabled('feature_stamps_enabled', $projectId);
        $this->shopSettingRepository->setValue('use_stamps', $useStamps, 'boolean', $projectId);
        if ($useStamps === false) {
            $this->shopSettingRepository->setValue('use_transaction_stamps', false, 'boolean', $projectId);
        }
    }
}
