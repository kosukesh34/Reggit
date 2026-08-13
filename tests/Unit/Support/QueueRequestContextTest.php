<?php

namespace Tests\Unit\Support;

use App\Support\QueueRequestContext;
use Illuminate\Http\Request;
use Tests\TestCase;

class QueueRequestContextTest extends TestCase
{
    public function test_queue_token_はクエリを優先すること(): void
    {
        $request = Request::create('/api/queue/me', 'GET', [
            'queue_token' => 'direct-token',
            'liff.state' => 'queue_token=embedded-token&tab=queue',
        ]);

        $this->assertSame('direct-token', QueueRequestContext::queueToken($request));
    }

    public function test_queue_token_はphpが変換したliff_stateから取れること(): void
    {
        $request = Request::create('/api/queue/me', 'GET', [
            'liff_state' => 'queue_token=from-php-query&tab=queue',
        ]);

        $this->assertSame('from-php-query', QueueRequestContext::queueToken($request));
    }

    public function test_queue_token_はliff_stateドットキーからも取れること(): void
    {
        $request = Request::create('/api/queue/me', 'GET', [
            'liff.state' => 'project_key=default&queue_token=from-liff-state&tab=queue',
        ]);

        $this->assertSame('from-liff-state', QueueRequestContext::queueToken($request));
        $this->assertNull(QueueRequestContext::queueId($request));
    }

    public function test_queue_id_はliff_stateから取れること(): void
    {
        $request = Request::create('/api/queue/me', 'GET', [
            'liff.state' => 'queue_id=42&tab=queue',
        ]);

        $this->assertSame(42, QueueRequestContext::queueId($request));
        $this->assertNull(QueueRequestContext::queueToken($request));
    }

    public function test_queue_token_はurlエンコードされたliff_stateから取れること(): void
    {
        $request = Request::create('/api/queue/me', 'GET', [
            'liff.state' => rawurlencode('queue_token=encoded-token&tab=queue'),
        ]);

        $this->assertSame('encoded-token', QueueRequestContext::queueToken($request));
    }

    public function test_queue_token_はrefererから取れること(): void
    {
        $request = Request::create('/api/queue/me', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://example.test/liff/default?queue_token=from-referer&tab=queue',
        ]);

        $this->assertSame('from-referer', QueueRequestContext::queueToken($request));
    }

    public function test_queue_token_はliff_redirect_uriから取れること(): void
    {
        $request = Request::create('/api/queue/me', 'GET', [
            'liffRedirectUri' => 'https://example.test/liff/default?queue_token=from-redirect&tab=queue',
        ]);

        $this->assertSame('from-redirect', QueueRequestContext::queueToken($request));
    }

    public function test_識別子が無いときはnullを返すこと(): void
    {
        $request = Request::create('/api/queue/me', 'GET');

        $this->assertNull(QueueRequestContext::queueToken($request));
        $this->assertNull(QueueRequestContext::queueId($request));
    }

    public function test_queue_id_はクエリを埋め込みより優先すること(): void
    {
        $request = Request::create('/api/queue/me', 'GET', [
            'queue_id' => '9',
            'liff_state' => 'queue_id=42&tab=queue',
        ]);

        $this->assertSame(9, QueueRequestContext::queueId($request));
    }
}
