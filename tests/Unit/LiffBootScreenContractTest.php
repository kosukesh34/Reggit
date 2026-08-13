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
}
