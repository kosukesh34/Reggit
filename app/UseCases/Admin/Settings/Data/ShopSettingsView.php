<?php

namespace App\UseCases\Admin\Settings\Data;

final class ShopSettingsView
{
    /**
     * @param  list<array{id: int, name: string, price: int}>  $products
     * @param  list<int>  $featuredProductIds
     * @param  array<string, int>  $shippingFeesByPrefecture
     * @param  list<string>  $prefectures
     * @param  array{legal_notice: array<string, string>, free_shipping_threshold: ?int, delivery_date_enabled: bool, delivery_lead_days: int, delivery_range_days: int, delivery_time_slots: list<string>, invoice_registration_number: string}  $operations
     */
    public function __construct(
        public readonly bool $shopEnabled,
        public readonly int $shippingFee,
        public readonly array $shippingFeesByPrefecture,
        public readonly array $prefectures,
        public readonly string $heroHeadline,
        public readonly string $heroSubcopy,
        public readonly string $heroImageUrl,
        public readonly array $featuredProductIds,
        public readonly array $products,
        public readonly string $publicShopUrl,
        public readonly string $customDomainUrl,
        public readonly array $operations,
        public readonly string $legalNoticeUrl,
    ) {}
}
