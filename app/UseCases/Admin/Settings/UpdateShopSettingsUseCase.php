<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Models\Product;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\Settings\Data\UpdateShopSettingsInput;

class UpdateShopSettingsUseCase
{
    public function __construct(
        private FeatureSettings $settings,
        private PublicImageStorageService $images,
    ) {}

    public function handle(UpdateShopSettingsInput $input): void
    {
        $this->settings->setValue('feature_shop_enabled', $input->shopEnabled, 'boolean', $input->projectId);
        $this->settings->setValue('shop_shipping_fee', $input->shippingFee, 'integer', $input->projectId);
        $this->settings->setValue(
            'shop_shipping_fees_by_prefecture',
            $input->shippingFeesByPrefecture,
            'json',
            $input->projectId,
        );
        $this->settings->setValue('shop_hero_headline', $input->heroHeadline, 'string', $input->projectId);
        $this->settings->setValue('shop_hero_subcopy', $input->heroSubcopy, 'string', $input->projectId);

        $allowedIds = Product::query()
            ->where('project_id', $input->projectId)
            ->whereIn('id', $input->featuredProductIds)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $this->settings->setValue('shop_featured_product_ids', $allowedIds, 'json', $input->projectId);

        if ($input->heroImage !== null) {
            $this->settings->setValue(
                'shop_hero_image_url',
                $this->images->store($input->heroImage, $input->projectId, PublicImageStorageService::DIR_SHOP_HERO),
                'string',
                $input->projectId,
            );
        } elseif ($input->clearHeroImage) {
            $this->settings->setValue('shop_hero_image_url', '', 'string', $input->projectId);
        }
    }
}
