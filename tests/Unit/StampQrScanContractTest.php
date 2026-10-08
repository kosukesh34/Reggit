<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class StampQrScanContractTest extends TestCase
{
    public function test_スタンプ付与urlは自サイトのapiだけを許可すること(): void
    {
        $root = dirname(__DIR__, 2);
        $grant = (string) file_get_contents($root.'/resources/js/liff-ui/utils/stampGrant.ts');
        $api = (string) file_get_contents($root.'/resources/js/liff-ui/services/reggitApi.ts');
        $this->assertStringContainsString('parsed.origin !== window.location.origin', $grant);
        $this->assertStringContainsString('grantUrl.origin !== window.location.origin', $api);
        $this->assertStringContainsString('STAMP_GRANT_INVALID_QR_MESSAGE', $grant);
    }

    public function test_ネイティブスタンプ画面は付与完了後にapiを再取得すること(): void
    {
        $root = dirname(__DIR__, 2);
        $page = (string) file_get_contents($root.'/resources/js/liff-ui/native/NativeStampsPage.tsx');
        $hook = (string) file_get_contents($root.'/resources/js/liff-ui/hooks/useStampGrantFromUrl.ts');
        $this->assertStringContainsString('useStampGrantFromUrl()', $page);
        $this->assertStringContainsString('grant.phase === "success") void reload()', $page);
        $this->assertStringContainsString('parseStampGrant(raw)', $hook);
        $this->assertStringContainsString('await addStamp(payload.value)', $hook);
        $this->assertStringContainsString('clearStampGrantCodeFromUrl()', $hook);
        $this->assertStringContainsString('setPhase("error")', $hook);
    }

    public function test_liff_stateの付与トークンをクエリから取り出せること(): void
    {
        $token = 'abcdef123456';
        parse_str('project_key=shop&tab=stamps&g='.$token, $state);

        $this->assertSame($token, $state['g']);
    }
}
