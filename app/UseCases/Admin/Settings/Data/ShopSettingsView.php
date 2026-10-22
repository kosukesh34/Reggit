<?php

namespace App\UseCases\Admin\Settings\Data;

final class ShopSettingsView
{
    /**
     * @param  list<array{id: int, name: string, price: int}>  $products
     * @param  list<int>  $featuredProductIds
     * @param  array<string, int>  $shippingFeesByPrefecture
     * @param  list<string>  $prefectures
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
    ) {}
}
