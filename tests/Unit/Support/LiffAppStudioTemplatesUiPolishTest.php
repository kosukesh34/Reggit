<?php

namespace Tests\Unit\Support;

use App\Support\LiffAppStudioTemplates;
use PHPUnit\Framework\TestCase;

class LiffAppStudioTemplatesUiPolishTest extends TestCase
{
    /**
     * @return array<string, mixed>|null
     */
    private function findTemplate(string $id): ?array
    {
        foreach (LiffAppStudioTemplates::catalog() as $tpl) {
            if (($tpl['id'] ?? '') === $id) {
                return $tpl;
            }
        }

        return null;
    }

    public function test_カフェホームはステータス→主役→クイック導線の順である(): void
    {
        $cafe = $this->findTemplate('rg-suite-cafe');
        $this->assertIsArray($cafe);

        $home = $cafe['layouts']['home'] ?? [];
        $types = array_column($home, 'type');
        $this->assertSame(['member_summary', 'stats_row', 'hero', 'quick_actions', 'button'], $types);
        $this->assertSame('rewards', $home[0]['design'] ?? null);
        $this->assertSame('sticky', $home[4]['design'] ?? null);
        $this->assertSame('#products', $home[4]['action'] ?? null);
        $this->assertSame('sand', $cafe['theme']['theme_preset'] ?? null);
        $this->assertSame('#00754A', $cafe['theme']['accent_color'] ?? null);
        $this->assertSame('ticket', $cafe['theme']['coupon_screen_design'] ?? null);
    }

    public function test_菓子ホームは会員ステータスと会員証クーポン2導線である(): void
    {
        $sweets = $this->findTemplate('rg-suite-sweets');
        $this->assertIsArray($sweets);

        $home = $sweets['layouts']['home'] ?? [];
        $this->assertSame('member_summary', $home[0]['type'] ?? null);
        $this->assertSame('minimal', $home[0]['design'] ?? null);

        $qa = null;
        foreach ($home as $block) {
            if (($block['type'] ?? '') === 'quick_actions') {
                $qa = $block;
                break;
            }
        }
        $this->assertIsArray($qa);
        $labels = array_column($qa['actions'] ?? [], 'label');
        $this->assertSame(['会員証', 'クーポン'], $labels);
    }

    public function test_注文ホームは受取カードが先頭である(): void
    {
        $order = $this->findTemplate('rg-suite-order');
        $this->assertIsArray($order);

        $home = $order['layouts']['home'] ?? [];
        $this->assertSame('card', $home[0]['type'] ?? null);
        $this->assertStringContainsString('受取番号', (string) ($home[0]['title'] ?? ''));
        $this->assertStringContainsString('2袋', (string) ($home[0]['description'] ?? ''));
        $types = array_column($home, 'type');
        $this->assertSame(['card', 'stats_row', 'heading', 'data', 'quick_actions', 'button'], $types);
        $this->assertNotContains('slider', $types);
    }

    public function test_小売メニューに会員証タブがある(): void
    {
        $retail = $this->findTemplate('rg-suite-retail');
        $this->assertIsArray($retail);

        $member = null;
        foreach ($retail['menu'] ?? [] as $item) {
            if (($item['tab'] ?? '') === 'member') {
                $member = $item;
                break;
            }
        }
        $this->assertIsArray($member);
        $this->assertSame('会員証', $member['label'] ?? null);
    }
}
