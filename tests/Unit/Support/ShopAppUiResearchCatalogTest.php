<?php

namespace Tests\Unit\Support;

use App\Support\ShopAppUiResearchCatalog;
use Tests\TestCase;

class ShopAppUiResearchCatalogTest extends TestCase
{
    public function test_summaryと原則と代表スクショを読める(): void
    {
        $summary = ShopAppUiResearchCatalog::summary();
        $this->assertGreaterThan(0, $summary['brands']);
        $this->assertGreaterThan(0, $summary['with_app']);
        $this->assertNotEmpty($summary['by_category']);

        $principles = ShopAppUiResearchCatalog::principles();
        $this->assertCount(6, $principles);
        $this->assertSame('P1', $principles[0]['id']);

        $featured = ShopAppUiResearchCatalog::featuredShots();
        $this->assertCount(8, $featured);
        $this->assertTrue($featured[0]['exists']);
    }

    public function test_asset_pathは許可パスのみ解決する(): void
    {
        $ok = ShopAppUiResearchCatalog::resolveAssetPath('app-ui-screenshots/_featured/01-starbucks-rewards.jpg');
        $this->assertNotNull($ok);
        $this->assertFileExists($ok);

        $this->assertNull(ShopAppUiResearchCatalog::resolveAssetPath('../.env'));
        $this->assertNull(ShopAppUiResearchCatalog::resolveAssetPath('app-ui-screenshots/../summary.json'));
    }

    public function test_highlight_brandsは評価件数順である(): void
    {
        $brands = ShopAppUiResearchCatalog::highlightBrands(5);
        $this->assertNotEmpty($brands);
        $this->assertArrayHasKey('brand', $brands[0]);
        $this->assertArrayHasKey('appStoreUrl', $brands[0]);

        for ($i = 1, $n = count($brands); $i < $n; $i++) {
            $this->assertGreaterThanOrEqual(
                $brands[$i]['userRatingCount'],
                $brands[$i - 1]['userRatingCount']
            );
        }
    }
}
