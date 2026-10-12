<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Support\HelpMarkdown;
use App\Util\AdminContext;
use Tests\TestCase;

class HelpMarkdownTest extends TestCase
{
    public function test_numbered_steps_are_rendered_as_an_ordered_list(): void
    {
        $html = (new HelpMarkdown)->toHtml("1. 画面を開く\n2. 保存する\n\n- 注意");
        $this->assertStringContainsString('<ol><li>画面を開く</li><li>保存する</li></ol><ul><li>注意</li></ul>', $html);
    }

    public function test_links_use_current_project_and_escape_labels_and_queries(): void
    {
        AdminContext::setCurrentProject(new Project(['project_key' => 'brand-one']));
        $html = (new HelpMarkdown)->toHtml('[設定を開く](/admin/{project_key}/settings?tab=a&mode=b)');
        $this->assertStringContainsString('href="/admin/brand-one/settings?tab=a&amp;mode=b"', $html);
        $this->assertStringContainsString('&lt;script&gt;', (new HelpMarkdown)->toHtml('[<script>](/admin/login)'));
    }

    public function test_system_preview_links_to_project_selection_without_guessing_brand(): void
    {
        AdminContext::setCurrentProject(null);
        $html = (new HelpMarkdown)->toHtml('[画面を開く](/admin/{project_key}/settings)');
        $this->assertStringContainsString('href="/admin"', $html);
    }

    public function test_links_reject_external_protocol_relative_and_executable_urls(): void
    {
        $renderer = new HelpMarkdown;
        foreach (['javascript:alert(1)', '//example.com', 'https://example.com', '/admin/login"onclick="alert(1)', '/admin/../login\\evil'] as $url) {
            $this->assertStringNotContainsString('<a ', $renderer->toHtml('[危険]('.$url.')'));
        }
    }

    public function test_htmlを逃がして見出しと箇条書きにすること(): void
    {
        $html = (new HelpMarkdown)->toHtml("## 見出し\n\n<script>alert(1)</script>\n\n- **太字**の項目\n");

        $this->assertStringContainsString('<h2>見出し</h2>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('<strong>太字</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_help_images_allow_only_local_help_paths(): void
    {
        $renderer = new HelpMarkdown;
        $html = $renderer->toHtml('![画面](/images/help/help-members.png)');
        $this->assertStringContainsString('<img src="/images/help/help-members.png"', $html);
        $this->assertStringNotContainsString('<img', $renderer->toHtml('![画面](javascript:alert(1))'));
        $this->assertStringNotContainsString('<img', $renderer->toHtml('![画面](https://example.com/a.png)'));
    }

    public function test_sections_have_navigation_and_zoomable_images(): void
    {
        $html = (new HelpMarkdown)->toHtml("## 1. 画面を開く\n\n![赤枠のボタン](/images/help/help-members.png)\n\n## 注意\n\n確認してください");
        $this->assertStringContainsString('href="#help-section-1"', $html);
        $this->assertStringContainsString('id="help-section-2"', $html);
        $this->assertStringContainsString('help-guide-section--caution', $html);
        $this->assertStringContainsString('target="_blank" rel="noopener"', $html);
        $this->assertStringContainsString('<figcaption>赤枠のボタン</figcaption>', $html);
    }
}
