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

    public function test_ホームにcrmレイアウトと店舗ギャラリーがあること(): void
    {
        $root = dirname(__DIR__, 2);
        $home = (string) file_get_contents($root.'/resources/js/liff-ui/pages/HomePage.tsx');
        $types = (string) file_get_contents($root.'/resources/js/liff-ui/types/customization.ts');
        $gallery = (string) file_get_contents($root.'/resources/js/liff-ui/components/home/HomeGallery.tsx');
        $projectStore = (string) file_get_contents($root.'/resources/js/liff-ui/config/projectStore.ts');
        $crmCss = (string) file_get_contents($root.'/resources/js/liff-ui/styles/globals.css');

        $this->assertStringContainsString('crm: ["notice", "membership", "news", "gallery"]', $home);
        $this->assertStringContainsString('HomeGallery', $home);
        $this->assertStringNotContainsString('FeaturedContent', $home);
        $this->assertStringContainsString('compact={layout === "crm"}', $home);
        $customization = (string) file_get_contents($root.'/resources/js/liff-ui/config/customization.ts');
        $this->assertStringContainsString('MEMBER_CRM_NAV_TABS', $customization);
        $this->assertStringContainsString('storeExperienceMap.store_cafe', $customization);
        $this->assertStringContainsString('const useMemberCrm = store.storeId === MEMBER_CRM_STORE_ID;', $customization);
        $this->assertStringContainsString('MembershipQr', (string) file_get_contents($root.'/resources/js/liff-ui/pages/MembershipPage.tsx'));
        $this->assertStringContainsString('| "crm"', $types);
        $this->assertStringContainsString('showGallery', $types);
        $this->assertStringContainsString('fetchGallery', $gallery);
        $this->assertStringContainsString('おすすめ商品', $gallery);
        $this->assertStringContainsString('写真を追加', $gallery);
        $this->assertStringContainsString("heroImage: ''", $projectStore);
        $this->assertStringContainsString("featuredImage: ''", $projectStore);
        $this->assertStringContainsString('会員証を表示する', (string) file_get_contents($root.'/resources/js/liff-ui/components/home/MembershipSummary.tsx'));
        $this->assertStringContainsString('membership-summary__pmark', (string) file_get_contents($root.'/resources/js/liff-ui/components/home/MembershipSummary.tsx'));
        $this->assertStringContainsString('membership-crm-sheet', (string) file_get_contents($root.'/resources/js/liff-ui/pages/MembershipPage.tsx'));
        $this->assertStringContainsString('background: var(--color-primary);', $crmCss);
        $this->assertStringContainsString('.home-page--layout-crm .membership-summary--crm', $crmCss);
    }
}
