<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class AppImagesSettingsView
{
    /** @param list<string> $homeImageUrls */
    public function __construct(public string $frontendHeaderText, public string $frontendLogoUrl, public string $stampDefaultImageUrl, public string $couponDefaultImageUrl, public string $navigationHeaderImageUrl, public array $homeImageUrls) {}
}
