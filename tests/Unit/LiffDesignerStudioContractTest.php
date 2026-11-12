<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LiffDesignerStudioContractTest extends TestCase
{
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
