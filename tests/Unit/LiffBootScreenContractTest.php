<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LiffBootScreenContractTest extends TestCase
{
    public function test_起動オーバーレイはデータと会員ゲートが揃ってから外すこと(): void
    {
        $root = dirname(__DIR__, 2);
        $shell = (string) file_get_contents($root.'/resources/js/liff-ui/components/layout/AppShell.tsx');
        $router = (string) file_get_contents($root.'/resources/js/liff-ui/app/router.tsx');

        $this->assertStringContainsString('gateSettled', $shell);
        $this->assertStringContainsString('dataSettled', $shell);
        $this->assertStringContainsString('liff-ui-ready', $shell);
        $this->assertStringContainsString('[dataSettled, gateSettled]', $shell);
        $this->assertStringContainsString('gateSettled={gateSettled}', $router);
        $this->assertStringContainsString('navigate(path, { replace: true })', $router);
    }

    public function test_本番機能画面はネイティブapi画面を使いデモに落ちないこと(): void
    {
        $root = dirname(__DIR__, 2);
        $feature = (string) file_get_contents($root.'/resources/js/liff-ui/pages/FeaturePage.tsx');
        $resource = (string) file_get_contents($root.'/resources/js/liff-ui/native/NativeResource.tsx');
        $this->assertStringContainsString('<NativeCouponsPage />', $feature);
        $this->assertStringContainsString('<NativeReservationsPage />', $feature);
        $this->assertStringNotContainsString('Demo', $feature);
        $this->assertStringContainsString('demo = isDemoDataMode()', $resource);
        $this->assertStringContainsString('if (demo) { setLoading(false); return; }', $resource);
        $this->assertStringContainsString('setData(await nativeRequest<T>(path))', $resource);
        $this->assertStringContainsString('if (error || empty)', $resource);
    }

    public function test_デモとデザイナープレビューの会員証はapiを待たないこと(): void
    {
        $root = dirname(__DIR__, 2);
        $page = (string) file_get_contents($root.'/resources/js/liff-ui/pages/MembershipPage.tsx');
        $resource = (string) file_get_contents($root.'/resources/js/liff-ui/native/NativeResource.tsx');
        $this->assertStringContainsString('window.__IS_DESIGNER_EMBED_PREVIEW__ === true', $page);
        $this->assertStringContainsString('isDemoDataMode() || adminPreview', $page);
        $this->assertStringContainsString('useState(!demo)', $resource);
    }

    public function test_店舗オーダーは店内注文フラグをネイティブ注文画面へ渡すこと(): void
    {
        $root = dirname(__DIR__, 2);
        $feature = (string) file_get_contents($root.'/resources/js/liff-ui/pages/FeaturePage.tsx');
        $products = (string) file_get_contents($root.'/resources/js/liff-ui/native/NativeProductsPage.tsx');
        $this->assertStringContainsString('case "mobileOrder": case "dineInOrder":', $feature);
        $this->assertStringContainsString('dineIn={feature === "dineInOrder"}', $feature);
        $this->assertStringContainsString('createOrder', $products);
        $this->assertStringContainsString('PaymentBrandPicker', $products);
    }

    public function test_会員証はウォレットサマリーを定期購読して即時反映すること(): void
    {
        $root = dirname(__DIR__, 2);
        $manager = (string) file_get_contents($root.'/resources/js/frontend/managers/MemberManager.ts');
        $page = (string) file_get_contents($root.'/resources/js/liff-ui/pages/MembershipPage.tsx');
        $resource = (string) file_get_contents($root.'/resources/js/liff-ui/native/NativeResource.tsx');
        $stamps = (string) file_get_contents($root.'/resources/js/liff-ui/native/NativeStampsPage.tsx');

        $this->assertStringContainsString("listen('.wallet.updated'", $manager);
        $this->assertStringContainsString('liff.member.', $manager);
        $this->assertStringContainsString('stamps_total: normalized.stamps_total', $manager);
        $this->assertStringNotContainsString('_walletSummaryTimer', $manager);
        $this->assertStringNotContainsString('window.setInterval', $manager);
        $echo = (string) file_get_contents($root.'/resources/js/frontend/realtime/liffEcho.ts');
        $this->assertStringContainsString('__REVERB_WS_HOST__', $echo);
        $scripts = (string) file_get_contents($root.'/resources/views/frontend/partials/_scripts-config.blade.php');
        $this->assertStringContainsString('reverb-ws-host', $scripts);
        $this->assertStringContainsString('useNativeResource("/api/member-profile"', $page);
        $this->assertStringContainsString('useNativeResource("/api/stamps"', $stamps);
        $this->assertStringContainsString('document.addEventListener("liff-wallet-summary-updated", update)', $resource);
        $this->assertStringContainsString('document.removeEventListener("liff-wallet-summary-updated", update)', $resource);
    }

    public function test_ログイン済みの非会員は会員証ページで登録フォームを出すこと(): void
    {
        $root = dirname(__DIR__, 2);
        $page = (string) file_get_contents($root.'/resources/js/liff-ui/pages/MembershipPage.tsx');
        $gate = (string) file_get_contents($root.'/resources/js/frontend/hooks/useMembershipGate.ts');
        $router = (string) file_get_contents($root.'/resources/js/liff-ui/app/router.tsx');

        $this->assertStringContainsString('window.__LINE_USER_ID__ || isDemoDataMode()', $page);
        $this->assertStringContainsString('<MemberRegisterHost mode="register"', $page);
        $this->assertStringContainsString('shouldShowStartupLoginPopup()', $gate);
        $this->assertStringContainsString('liff-member-open-login-popup', $gate);
        $this->assertStringContainsString('<LoginPromptPopup />', $router);

        $api = (string) file_get_contents($root.'/resources/js/frontend/features/member/api.ts');
        $this->assertStringContainsString('window.__IS_LIFF__ === true', $api);
        $this->assertStringContainsString('openRegister(false)', $api);
    }
}
