<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class StampQrScanContractTest extends TestCase
{
    public function test_アプリ内カメラはgetusermediaとjsqrで読むこと(): void
    {
        $root = dirname(__DIR__, 2);
        $popup = (string) file_get_contents($root.'/resources/js/liff-ui/components/stamp/StampScannerPopup.tsx');

        $this->assertStringContainsString("from 'jsqr'", $popup);
        $this->assertStringContainsString('webkitGetUserMedia', $popup);
        $this->assertStringContainsString('width: 640', $popup);
        $this->assertStringContainsString('height: 420', $popup);
        $this->assertStringContainsString("facingMode: 'environment'", $popup);
        $this->assertStringContainsString('stamp-scanner__live', $popup);
        $this->assertStringNotContainsString('BarcodeDetector', $popup);
        $this->assertStringNotContainsString('CenterPopup', $popup);
        $this->assertStringNotContainsString('この端末ではアプリ内カメラに対応していません', $popup);
    }

    public function test_カメラ起動はクリックのユーザー操作内で始めること(): void
    {
        $root = dirname(__DIR__, 2);
        $popup = (string) file_get_contents($root.'/resources/js/liff-ui/components/stamp/StampScannerPopup.tsx');
        $stampsView = (string) file_get_contents($root.'/resources/js/frontend/features/stamps/StampsView.tsx');
        $simpleRewards = (string) file_get_contents($root.'/resources/js/liff-ui/pages/SimpleRewardsPage.tsx');
        $rewardsDemo = (string) file_get_contents($root.'/resources/js/liff-ui/features/demo/RewardsDemo.tsx');

        $this->assertStringContainsString('export function openStampScanner', $popup);
        $this->assertStringContainsString('openStampScanner(setScannerOpen)', $stampsView);
        $this->assertStringContainsString('openStampScanner(setScannerOpen)', $simpleRewards);
        $this->assertStringContainsString('openStampScanner(setScannerOpen)', $rewardsDemo);
        $this->assertStringNotContainsString('onClick={() => setScannerOpen(true)}', $stampsView);
        $this->assertStringNotContainsString('onClick={() => setScannerOpen(true)}', $simpleRewards);
    }

    public function test_liff_stateの付与トークンをクエリから取り出せること(): void
    {
        $token = 'abcdef123456';
        parse_str('project_key=shop&tab=stamps&g='.$token, $state);

        $this->assertSame($token, $state['g']);
    }
}
