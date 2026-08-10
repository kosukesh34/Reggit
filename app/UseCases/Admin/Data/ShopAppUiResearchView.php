<?php

namespace App\UseCases\Admin\Data;

/** 店舗アプリ UI 学習データの表示用。不変。 */
class ShopAppUiResearchView
{
    /**
     * @param  array{brands: int, with_app: int, without_app: int, by_category: array<string, array{total: int, with_app: int}>}  $summary
     * @param  array<int, array{id: string, title: string, body: string, source: string}>  $principles
     * @param  array<int, array{file: string, title: string, note: string, template: string, exists: bool}>  $featuredShots
     * @param  array<int, array{source: string, target: string}>  $templateMappings
     * @param  array<int, array{brand: string, categories: string, trackName: string, appStoreUrl: string, screenshotCount: int, averageUserRating: float, userRatingCount: int}>  $highlightBrands
     */
    public function __construct(
        public readonly array $summary,
        public readonly array $principles,
        public readonly array $featuredShots,
        public readonly array $templateMappings,
        public readonly array $highlightBrands,
        public readonly string $learningsHtml,
    ) {}
}
