<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\SettingValueWriter;
use App\Services\Admin\HomeImagesUpdater;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\Settings\Data\UpdateAppImagesInput;

class UpdateAppImagesUseCase
{
    public function __construct(private SettingValueWriter $settings, private PublicImageStorageService $images, private HomeImagesUpdater $homeImages) {}

    public function handle(UpdateAppImagesInput $input): void
    {
        if ($input->homeAssets !== null) {
            $this->homeImages->update($input->homeAssets);
        }
        if ($input->headerText !== null) {
            $this->settings->setValue('frontend_header_text', $input->headerText, 'string', $input->projectId);
        }
        foreach ([
            ['frontend_logo_url', $input->logo, $input->clearLogo, PublicImageStorageService::DIR_FRONTEND_LOGO],
            ['stamp_default_image_url', $input->stampImage, $input->clearStampImage, PublicImageStorageService::DIR_STAMP_DEFAULT],
            ['coupon_default_image_url', $input->couponImage, $input->clearCouponImage, PublicImageStorageService::DIR_COUPON_DEFAULT],
        ] as [$key, $file, $clear, $directory]) {
            if ($file !== null) {
                $this->settings->setValue($key, $this->images->store($file, $input->projectId, $directory), 'string', $input->projectId);
            } elseif ($clear) {
                $this->settings->setValue($key, '', 'string', $input->projectId);
            }
        }
    }
}
