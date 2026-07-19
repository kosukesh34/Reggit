<?php

namespace Tests\Unit;

use App\Support\QueueLaunchUrlBuilder;
use Tests\TestCase;

class QueueLaunchUrlBuilderTest extends TestCase
{
    public function test_build_open_url(): void
    {
        $url = QueueLaunchUrlBuilder::buildOpenUrl('default', 'abcToken');

        $this->assertSame(url('/queue/open/default/abcToken'), $url);
    }

    public function test_build_native_scheme_url(): void
    {
        $url = QueueLaunchUrlBuilder::buildNativeSchemeUrl('shop-a', 'tok123');

        $this->assertStringStartsWith('reggit://queue?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('shop-a', $query['project_key']);
        $this->assertSame('tok123', $query['queue_token']);
    }
}
