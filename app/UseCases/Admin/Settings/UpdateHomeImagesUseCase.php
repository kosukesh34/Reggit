<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\HomeSettingsRepository;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\Settings\Data\UpdateHomeImagesInput;

class UpdateHomeImagesUseCase
{
    public function __construct(private HomeSettingsRepository $settings, private PublicImageStorageService $images) {}

    public function handle(UpdateHomeImagesInput $input): void
    {
        $urls = null;
        if ($input->galleryPresent) {
            $existing = $this->settings->getValue('home_image_urls', null, $input->projectId);
            if (! is_array($existing)) {
                $legacy = $this->settings->getValue('home_image_url', '', $input->projectId);
                $existing = $legacy ? [$legacy] : [];
            }
            $urls = array_values(array_filter($existing, fn ($url, $index) => ! in_array($index, $input->removed, true), ARRAY_FILTER_USE_BOTH));
            if (count($urls) + count($input->images) > 10) {
                throw new \InvalidArgumentException('ホーム画像は最大10枚まで登録できます。');
            }
            foreach ($input->images as $file) {
                $urls[] = $this->images->store($file, $input->projectId, PublicImageStorageService::DIR_HOME_IMAGE);
            }
        } elseif ($input->legacyImage !== null || $input->clearLegacy) {
            $urls = $input->legacyImage !== null ? [$this->images->store($input->legacyImage, $input->projectId, PublicImageStorageService::DIR_HOME_IMAGE)] : [];
        }
        if ($urls !== null) {
            $this->settings->setValue('home_image_urls', $urls, 'json', $input->projectId);
            $this->settings->setValue('home_image_url', $urls[0] ?? '', 'string', $input->projectId);
        }
        if ($input->headerImage !== null) {
            $this->settings->setValue('navigation_header_image_url', $this->images->store($input->headerImage, $input->projectId, PublicImageStorageService::DIR_NAVIGATION_HEADER), 'string', $input->projectId);
        } elseif ($input->clearHeader) {
            $this->settings->setValue('navigation_header_image_url', '', 'string', $input->projectId);
        }
    }
}
