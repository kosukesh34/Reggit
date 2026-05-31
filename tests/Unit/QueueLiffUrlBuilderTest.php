<?php

namespace Tests\Unit;

use App\Support\QueueLiffUrlBuilder;
use PHPUnit\Framework\TestCase;

class QueueLiffUrlBuilderTest extends TestCase
{
    public function test_builds_liff_state_url_for_liff_host(): void
    {
        $url = QueueLiffUrlBuilder::appendQueueParams(
            'https://liff.line.me/2008962885-AbCdEfGh',
            'default',
            'queue_token',
            'abc123token'
        );

        $this->assertStringStartsWith('https://liff.line.me/2008962885-AbCdEfGh?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('liff.state', $query);
        parse_str($query['liff.state'], $state);
        $this->assertSame('default', $state['project_key']);
        $this->assertSame('abc123token', $state['queue_token']);
    }

    public function test_normalizes_miniapp_host_to_liff_line_me(): void
    {
        $url = QueueLiffUrlBuilder::appendQueueParams(
            'https://miniapp.line.me/2008962885-AbCdEfGh',
            'shop-a',
            'queue_token',
            'tok'
        );

        $this->assertStringStartsWith('https://liff.line.me/2008962885-AbCdEfGh?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        parse_str($query['liff.state'], $state);
        $this->assertSame('shop-a', $state['project_key']);
        $this->assertSame('tok', $state['queue_token']);
    }

    public function test_builds_web_url_for_non_liff_host(): void
    {
        $url = QueueLiffUrlBuilder::appendQueueParams(
            'https://example.test/liff/default',
            'default',
            'queue_token',
            'xyz'
        );

        $this->assertSame(
            'https://example.test/liff/default?project_key=default&queue_token=xyz',
            $url
        );
    }

    public function test_uses_queue_id_when_token_empty(): void
    {
        $url = QueueLiffUrlBuilder::appendQueueParams(
            'https://liff.line.me/2008962885-AbCdEfGh',
            'default',
            'queue_id',
            '42'
        );

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        parse_str($query['liff.state'], $state);
        $this->assertSame('42', $state['queue_id']);
    }
}
