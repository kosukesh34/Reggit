<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Services\Admin\PublicImageStorageService;
use Illuminate\Http\UploadedFile;

class UpdateFeatureSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
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
    ): void {
        $features = $this->shopSettingRepository->withPreservedLotteryManagedFlags($payload['features'] ?? [], $projectId);
        $this->shopSettingRepository->setFeatureFlags($features, $projectId);

        $this->shopSettingRepository->setValue('admin_sidebar_color', $payload['admin_sidebar_color'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('admin_header_color', $payload['admin_header_color'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('admin_primary_color', $payload['admin_primary_color'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('stamp_default_image_url', $payload['stamp_default_image_url'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('frontend_header_text', $payload['frontend_header_text'] ?? '', 'string', $projectId);

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
}
