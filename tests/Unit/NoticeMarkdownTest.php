<?php

namespace Tests\Unit;

use App\Support\NoticeMarkdown;
use Tests\TestCase;

class NoticeMarkdownTest extends TestCase
{
    public function test_markdown_and_hex_colors_are_rendered(): void
    {
        $html = (new NoticeMarkdown)->toHtml("## 更新情報\n\n**太字**\n\n- 項目\n\n[ヘルプ](https://example.com)\n\n[color=#b91c1c]**休業日**[/color]");
        $this->assertStringContainsString('<h2>更新情報</h2>', $html);
        $this->assertStringContainsString('<strong>太字</strong>', $html);
        $this->assertStringContainsString('<li>項目</li>', $html);
        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('<span style="color: #b91c1c"><strong>休業日</strong></span>', $html);
    }

    public function test_html_scripts_unsafe_links_and_invalid_colors_are_not_executable(): void
    {
        $html = (new NoticeMarkdown)->toHtml('<script>alert(1)</script>'."\n\n".'[危険](javascript:alert%281%29)'."\n\n".'[color=red;position:fixed]本文[/color]'."\n\n".'[color=#ff0000]<img src=x onerror=alert(1)>[/color]');
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->assertStringNotContainsString('style="color: red', $html);
    }
}
