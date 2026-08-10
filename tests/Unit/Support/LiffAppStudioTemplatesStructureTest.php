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
            'rg-suite-cafe',
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
