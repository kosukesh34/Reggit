<?php

namespace App\Support;

use App\Domain\Settings\Repositories\SettingValueReader;

final class HomeImageUrls
{
    /** @return list<string> */
    public static function forProject(SettingValueReader $settings, int $projectId): array
    {
        $urls = $settings->getValue('home_image_urls', null, $projectId);
        if (is_array($urls)) {
            return array_values(array_filter($urls, fn ($url) => is_string($url) && $url !== ''));
        }

        $legacy = $settings->getValue('home_image_url', '', $projectId);

        return is_string($legacy) && $legacy !== '' ? [$legacy] : [];
    }
}
