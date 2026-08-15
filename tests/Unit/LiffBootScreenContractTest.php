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

    public function test_本番機能画面はpreview以外でデモ画面に落ちないこと(): void
    {
        $root = dirname(__DIR__, 2);
        $feature = (string) file_get_contents($root.'/resources/js/liff-ui/pages/FeaturePage.tsx');
        $runtime = (string) file_get_contents($root.'/resources/js/liff-ui/context/RuntimeDataContext.tsx');

        $this->assertStringContainsString('if (mode !== "live" && mode !== "preview")', $feature);
        $this->assertStringContainsString('return <FeaturePageSkeleton />', $feature);
        $this->assertStringContainsString('window.__LIFF_READY__ !== true', $runtime);
        $this->assertStringContainsString('generation !== refreshGeneration.current', $runtime);
    }

    public function test_デモ会員証はpreviewでスケルトンに固定しないこと(): void
    {
        $root = dirname(__DIR__, 2);
        $page = (string) file_get_contents($root.'/resources/js/liff-ui/pages/MembershipPage.tsx');

        $this->assertStringContainsString('isDemoDataMode()', $page);
        $this->assertStringContainsString('mode === "preview"', $page);
        $this->assertStringContainsString('if (booting)', $page);
        $this->assertStringNotContainsString(
            'mode !== "live" && !(mode === "offline" && !loading)',
            $page,
        );
    }

    public function test_店舗オーダーはプロダクトページではなくオーダーデモを使うこと(): void
    {
        $root = dirname(__DIR__, 2);
        $feature = (string) file_get_contents($root.'/resources/js/liff-ui/pages/FeaturePage.tsx');

        $this->assertStringNotContainsString('<ProductsPage />', $feature);
        $this->assertStringContainsString('<OrderDemo dineIn={false}', $feature);
        $this->assertStringContainsString('<OrderDemo dineIn', $feature);
    }

    public function test_会員証はウォレットサマリーを定期購読して即時反映すること(): void
    {
        $root = dirname(__DIR__, 2);
        $manager = (string) file_get_contents($root.'/resources/js/frontend/managers/MemberManager.ts');
        $page = (string) file_get_contents($root.'/resources/js/liff-ui/pages/MembershipPage.tsx');
        $stamps = (string) file_get_contents($root.'/resources/js/frontend/features/stamps/StampsView.tsx');

        $this->assertStringContainsString("listen('.wallet.updated'", $manager);
        $this->assertStringContainsString('liff.member.', $manager);
        $this->assertStringContainsString('stamps_total: normalized.stamps_total', $manager);
        $this->assertStringNotContainsString('_walletSummaryTimer', $manager);
        $this->assertStringNotContainsString('window.setInterval', $manager);
        $echo = (string) file_get_contents($root.'/resources/js/frontend/realtime/liffEcho.ts');
        $this->assertStringContainsString('__REVERB_WS_HOST__', $echo);
        $scripts = (string) file_get_contents($root.'/resources/views/frontend/partials/_scripts-config.blade.php');
        $this->assertStringContainsString('reverb-ws-host', $scripts);
        $this->assertStringContainsString('liff-wallet-summary-updated', $page);
        $this->assertStringContainsString('fetchMemberContext({ force: true })', $page);
        $this->assertStringContainsString("document.addEventListener('liff-wallet-summary-updated'", $stamps);
        $this->assertStringContainsString('void reload(true)', $stamps);
    }

    public function test_会員証は登録イベントでフォームを出すこと(): void
    {
        $root = dirname(__DIR__, 2);
        $page = (string) file_get_contents($root.'/resources/js/liff-ui/pages/MembershipPage.tsx');
        $gate = (string) file_get_contents($root.'/resources/js/frontend/hooks/useMembershipGate.ts');
        $router = (string) file_get_contents($root.'/resources/js/liff-ui/app/router.tsx');

        $this->assertStringContainsString('liff-member-open-register', $page);
        $this->assertStringContainsString('<MemberRegisterHost mode="register"', $page);
        $this->assertStringContainsString('shouldShowStartupLoginPopup()', $gate);
        $this->assertStringContainsString('liff-member-open-login-popup', $gate);
        $this->assertStringContainsString('<LoginPromptPopup />', $router);

        $api = (string) file_get_contents($root.'/resources/js/frontend/features/member/api.ts');
        $this->assertStringContainsString('window.__IS_LIFF__ === true', $api);
        $this->assertStringContainsString('openRegister(false)', $api);
    }
}
