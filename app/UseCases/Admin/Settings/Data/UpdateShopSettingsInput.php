<?php

namespace App\UseCases\Admin\Settings\Data;

use Illuminate\Http\UploadedFile;

final class UpdateShopSettingsInput
{
    /**
     * @param  list<int>  $featuredProductIds
     * @param  array<string, int>  $shippingFeesByPrefecture
     */
    public function __construct(
        public readonly int $projectId,
        public readonly bool $shopEnabled,
        public readonly int $shippingFee,
        public readonly array $shippingFeesByPrefecture,
        public readonly string $heroHeadline,
        public readonly string $heroSubcopy,
        public readonly ?UploadedFile $heroImage,
        public readonly bool $clearHeroImage,
        public readonly array $featuredProductIds,
    ) {}
}
