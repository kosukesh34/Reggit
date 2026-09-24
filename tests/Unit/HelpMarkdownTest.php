<?php

namespace Tests\Unit;

use App\Support\HelpMarkdown;
use Tests\TestCase;

class HelpMarkdownTest extends TestCase
{
    public function test_htmlを逃がして見出しと箇条書きにすること(): void
    {
        $html = (new HelpMarkdown)->toHtml("## 見出し\n\n<script>alert(1)</script>\n\n- **太字**の項目\n");

        $this->assertStringContainsString('<h2>見出し</h2>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('<strong>太字</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}
