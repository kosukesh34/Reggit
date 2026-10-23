<?php

namespace App\UseCases\Admin\Settings\Data;

use Illuminate\Http\UploadedFile;

final class UpdateShopSettingsInput
{
    /**
     * @param  list<int>  $featuredProductIds
     * @param  array<string, int>  $shippingFeesByPrefecture
     * @param  array{legal_notice: array<string, string>, free_shipping_threshold: ?int, delivery_date_enabled: bool, delivery_lead_days: int, delivery_range_days: int, delivery_time_slots: list<string>, invoice_registration_number: string}  $operations
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
        public readonly array $operations,
    ) {}
}
