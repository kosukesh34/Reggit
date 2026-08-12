<?php

namespace Tests\Unit\Support;

use App\Support\LiffAppStudioTemplates;
use App\Support\LiffThemePresets;
use PHPUnit\Framework\TestCase;

class LiffAppStudioTemplatesStructureTest extends TestCase
{
    /** @return list<string> */
    private function suiteIds(): array
    {
        return [
            'rg-suite-loyalty',
            'rg-suite-order',
            'rg-suite-booking',
            'rg-suite-counter-operations',
            'rg-suite-appointment-operations',
            'rg-suite-cafe',
            'rg-suite-landing',
            'rg-suite-simple-rewards',
            'rg-suite-salon',
            'rg-suite-retail',
            'rg-suite-clinic',
            'rg-suite-delivery',
            'rg-suite-sweets',
        ];
    }

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

    public function test_全スイートのホーム先頭はステータスまたは主役ブロックである(): void
    {
        $allowedFirst = ['member_summary', 'card', 'hero', 'banner', 'slider', 'stats_row'];

        foreach ($this->suiteIds() as $id) {
            $suite = $this->findTemplate($id);
            $this->assertIsArray($suite, $id);

            $home = $suite['layouts']['home'] ?? [];
            $this->assertNotEmpty($home, $id);
            $first = (string) ($home[0]['type'] ?? '');
            $this->assertContains($first, $allowedFirst, $id.' first='.$first);
        }
    }

    public function test_ホームに商品一覧データがない_小売以外(): void
    {
        foreach ($this->suiteIds() as $id) {
            if ($id === 'rg-suite-retail') {
                continue;
            }

            $suite = $this->findTemplate($id);
            $this->assertIsArray($suite, $id);

            foreach ($suite['layouts']['home'] ?? [] as $block) {
                if (($block['type'] ?? '') !== 'data') {
                    continue;
                }
                $this->assertNotSame(
                    'products',
                    $block['source'] ?? null,
                    $id.' home must not include products data shelf'
                );
            }
        }
    }

    public function test_pastryとchocolateがテーマピッカーに載っている(): void
    {
        $ids = array_column(LiffThemePresets::all(), 'id');
        $this->assertContains('pastry', $ids);
        $this->assertContains('chocolate', $ids);
        $this->assertContains('citrus', $ids);
    }

    public function test_公開ランディングページスタイルは配色と主要導線を持つ(): void
    {
        $suite = $this->findTemplate('rg-suite-landing');
        $this->assertIsArray($suite);
        $this->assertSame('store', $suite['theme']['theme_preset'] ?? null);
        $this->assertSame('#0E7C66', $suite['theme']['accent_color'] ?? null);
        $this->assertSame('#F1F5F4', $suite['theme']['bg_color'] ?? null);

        $home = $suite['layouts']['home'] ?? [];
        $this->assertSame('hero', $home[0]['type'] ?? null);
        $this->assertSame('/images/landing/lp-hero-cafe.jpg', $home[0]['image_url'] ?? null);
        $this->assertStringContainsString('店舗に必要な機能', (string) ($home[0]['title'] ?? ''));

        $menuTabs = array_column($suite['menu'] ?? [], 'tab');
        $this->assertSame(['home', 'products', 'member', 'queue', 'coupons'], $menuTabs);
        $this->assertSame('all_tabs', $suite['scope'] ?? null);
    }

    public function test_シンプル会員証は4タブだけで構成される(): void
    {
        $suite = $this->findTemplate('rg-suite-simple-rewards');
        $this->assertIsArray($suite);
        $this->assertSame('#171717', $suite['theme']['accent_color'] ?? null);
        $this->assertSame('compact', $suite['theme']['density'] ?? null);

        $menuTabs = array_column($suite['menu'] ?? [], 'tab');
        $this->assertSame(['home', 'member', 'coupons', 'stamps'], $menuTabs);
        $this->assertSame('member_summary', $suite['layouts']['home'][0]['type'] ?? null);
        $this->assertSame('all_tabs', $suite['scope'] ?? null);
    }

    public function test_店舗運営テンプレートは主要操作の配置が異なる(): void
    {
        $counter = $this->findTemplate('rg-suite-counter-operations');
        $appointment = $this->findTemplate('rg-suite-appointment-operations');
        $this->assertIsArray($counter);
        $this->assertIsArray($appointment);

        $this->assertSame('stats_row', $counter['layouts']['home'][0]['type'] ?? null);
        $this->assertSame('counter', $counter['layouts']['home'][1]['design'] ?? null);
        $this->assertSame(['home', 'products', 'queue', 'member', 'orders'], array_column($counter['menu'] ?? [], 'tab'));

        $this->assertSame('card', $appointment['layouts']['home'][0]['type'] ?? null);
        $this->assertSame('next_appointment', $appointment['layouts']['home'][0]['design'] ?? null);
        $this->assertSame('slots', $appointment['layouts']['home'][2]['design'] ?? null);
        $this->assertSame(['home', 'reservations', 'member', 'coupons', 'gallery'], array_column($appointment['menu'] ?? [], 'tab'));
    }

    public function test_スイートはthemeとtab_settings_globalが一致する(): void
    {
        foreach ($this->suiteIds() as $id) {
            $suite = $this->findTemplate($id);
            $this->assertIsArray($suite, $id);

            $theme = $suite['theme'] ?? null;
            $global = $suite['tab_settings']['_global'] ?? null;
            $this->assertIsArray($theme, $id);
            $this->assertIsArray($global, $id);
            $this->assertSame($theme['theme_preset'] ?? null, $global['theme_preset'] ?? null, $id);
            $this->assertSame($theme['accent_color'] ?? null, $global['accent_color'] ?? null, $id);
            $this->assertNotEmpty($suite['menu'] ?? [], $id);
        }
    }

    public function test_スイートのスタンプ見た目が業種ごとに分かれている(): void
    {
        $signatures = [];
        foreach ($this->suiteIds() as $id) {
            $suite = $this->findTemplate($id);
            $this->assertIsArray($suite, $id);
            $theme = $suite['theme'] ?? [];
            $this->assertNotEmpty($theme['stamp_screen_design'] ?? '', $id);

            $stampBlock = null;
            foreach ($suite['layouts']['stamps'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'data' && ($block['source'] ?? '') === 'stamps') {
                    $stampBlock = $block;
                    break;
                }
            }
            $this->assertIsArray($stampBlock, $id.' stamps data block');
            $this->assertNotEmpty($stampBlock['stamp_design'] ?? '', $id);

            $signatures[$id] = implode('|', [
                (string) ($theme['stamp_screen_design'] ?? ''),
                (string) ($theme['stamp_slot_style'] ?? ''),
                (string) ($theme['stamp_filled_style'] ?? ''),
                (string) ($stampBlock['stamp_design'] ?? ''),
                (string) ($stampBlock['stamp_slot_style'] ?? ''),
            ]);
        }

        $this->assertGreaterThanOrEqual(
            4,
            count(array_unique(array_values($signatures))),
            'stamp look signatures should vary across suites'
        );
    }
}
