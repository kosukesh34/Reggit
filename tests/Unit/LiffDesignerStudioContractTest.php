<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LiffDesignerStudioContractTest extends TestCase
{
    public function test_デザイナーは既定で会員crmテンプレだけを出すこと(): void
    {
        $root = dirname(__DIR__, 2);
        $admin = (string) file_get_contents($root.'/resources/js/liff-ui/pages/AdminCustomizePage.tsx');
        $templates = (string) file_get_contents($root.'/resources/js/liff-ui/config/appTemplates.ts');

        $this->assertStringContainsString('studioDesignerTemplates', $admin);
        $this->assertStringContainsString('designerTemplates(appConfig.stores)', $admin);
        $this->assertStringContainsString('こだわり設定', $admin);
        $this->assertStringContainsString('配色はそのまま', $admin);
        $this->assertStringContainsString('store_member_crm', $templates);
        $this->assertStringContainsString('STUDIO_TEMPLATE_IDS', $templates);
        $this->assertDoesNotMatchRegularExpression('/STUDIO_TEMPLATE_IDS = \[[^\]]*store_gallery/', $templates);
        $this->assertDoesNotMatchRegularExpression('/STUDIO_TEMPLATE_IDS = \[[^\]]*store_landing/', $templates);
        $this->assertStringNotContainsString('industryBlockLabels', $admin);
        $this->assertStringContainsString('HomeLayoutPicker', $admin);
        $this->assertStringContainsString('gallery/items', $admin);
        $this->assertStringContainsString('showGallery', $admin);
        $this->assertStringContainsString('おすすめ商品', $admin);
        $this->assertStringNotContainsString('showFeatured', $admin);
    }

    public function test_ホームは設定したレイアウトと店舗ギャラリーを表示すること(): void
    {
        $root = dirname(__DIR__, 2);
        $home = (string) file_get_contents($root.'/resources/js/liff-ui/pages/NativeHome.tsx');
        $config = (string) file_get_contents($root.'/resources/js/liff-ui/native/nativeConfig.tsx');
        $this->assertStringContainsString('NativeHome as HomePage', (string) file_get_contents($root.'/resources/js/liff-ui/pages/HomePage.tsx'));
        $this->assertStringContainsString('config.home?.layout', $home);
        $this->assertStringContainsString('buttons_gallery', $home);
        $this->assertStringContainsString('gallery_items', $home);
        $this->assertStringContainsString('selectedGallery', $home);
        $this->assertStringContainsString('HomeGalleryItem', $config);
        $this->assertStringContainsString('showMemberCode', $home);
        $this->assertStringContainsString('BarcodeEnlargeView', $home);
        $this->assertStringContainsString('MembershipBarcode', $home);
    }
}
