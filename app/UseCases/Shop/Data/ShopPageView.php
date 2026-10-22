<?php

namespace App\UseCases\Shop\Data;

use App\UseCases\ShopAuth\Data\ShopAuthUserView;

final class ShopPageView
{
    /**
     * @param  list<int>  $featuredProductIds
     * @param  array<string, int>  $shippingFeesByPrefecture
     * @param  list<string>  $prefectures
     * @param  array{primary: string, primaryDark: string}  $theme
     */
    public function __construct(
        public readonly string $projectKey,
        public readonly string $projectName,
        public readonly string $loginUrl,
        public readonly string $logoutUrl,
        public readonly ?ShopAuthUserView $user,
        public readonly string $heroHeadline,
        public readonly string $heroSubcopy,
        public readonly string $heroImageUrl,
        public readonly string $logoUrl,
        public readonly int $shippingFee,
        public readonly array $shippingFeesByPrefecture,
        public readonly array $prefectures,
        public readonly array $featuredProductIds,
        public readonly array $theme,
        public readonly string $passkeyRegisterOptionsUrl,
        public readonly string $passkeyRegisterSubmitUrl,
    ) {}
}
