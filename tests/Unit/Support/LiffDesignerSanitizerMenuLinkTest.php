<?php

namespace Tests\Unit\Support;

use App\Support\LiffDesignerSanitizer;
use PHPUnit\Framework\TestCase;

class LiffDesignerSanitizerMenuLinkTest extends TestCase
{
    public function test_sanitizeMenuItems_外部ページタブを許可すること(): void
    {
        $result = LiffDesignerSanitizer::sanitizeMenuItems([
            ['tab' => 'home', 'label' => 'ホーム', 'icon' => 'fa-house', 'enabled' => true],
            ['tab' => 'link_1', 'label' => '公式サイト', 'icon' => 'fa-globe', 'url' => 'https://example.com/page', 'open' => 'embed', 'enabled' => true],
            ['tab' => 'link_2', 'label' => '', 'icon' => '', 'url' => 'https://example.com/other', 'open' => 'invalid', 'enabled' => true],
        ]);

        $this->assertSame(['home', 'link_1', 'link_2'], array_column($result, 'tab'));
        $this->assertSame('https://example.com/page', $result[1]['url']);
        $this->assertSame('embed', $result[1]['open']);
        $this->assertSame('リンク', $result[2]['label']);
        $this->assertSame('fa-globe', $result[2]['icon']);
        $this->assertSame('embed', $result[2]['open']);
    }

    public function test_sanitizeMenuItems_external指定でもembedに正規化すること(): void
    {
        $result = LiffDesignerSanitizer::sanitizeMenuItems([
            ['tab' => 'link_1', 'label' => '公式', 'icon' => 'fa-globe', 'url' => 'https://example.com', 'open' => 'external', 'enabled' => true],
        ]);

        $this->assertCount(1, $result);
        $this->assertSame('embed', $result[0]['open']);
    }

    public function test_sanitizeMenuItems_httpsでないURLの外部ページタブは除外されること(): void
    {
        $result = LiffDesignerSanitizer::sanitizeMenuItems([
            ['tab' => 'link_1', 'label' => 'NG', 'icon' => 'fa-globe', 'url' => 'http://example.com', 'enabled' => true],
            ['tab' => 'link_2', 'label' => 'NG', 'icon' => 'fa-globe', 'url' => 'javascript:alert(1)', 'enabled' => true],
            ['tab' => 'link_3', 'label' => 'NG', 'icon' => 'fa-globe', 'enabled' => true],
        ]);

        $this->assertSame([], $result);
    }
}
