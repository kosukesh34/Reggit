<?php

namespace Tests\Unit\Support;

use App\Support\LiffAppStudioTemplates;
use PHPUnit\Framework\TestCase;

class LiffAppStudioTemplatesSweetsTest extends TestCase
{
    public function test_菓子土産スイートがカタログに含まれホームが会員証主導である(): void
    {
        $catalog = LiffAppStudioTemplates::catalog();
        $ids = array_column($catalog, 'id');

        $this->assertContains('rg-suite-sweets', $ids);
        $this->assertContains('rg-screen-barcode-dock', $ids);
        $this->assertContains('rg-screen-sweets-home', $ids);
        $this->assertContains('rg-screen-pickup-ready', $ids);

        $suite = null;
        foreach ($catalog as $tpl) {
            if (($tpl['id'] ?? '') === 'rg-suite-sweets') {
                $suite = $tpl;
                break;
            }
        }

        $this->assertIsArray($suite);
        $this->assertSame('sweets', $suite['category'] ?? null);
        $this->assertSame('pastry', $suite['theme']['theme_preset'] ?? null);

        $menuTabs = array_column($suite['menu'] ?? [], 'tab');
        $this->assertSame(['home', 'member', 'coupons', 'products', 'announcements'], $menuTabs);

        $home = $suite['layouts']['home'] ?? [];
        $this->assertNotEmpty($home);
        $this->assertSame('member_summary', $home[0]['type'] ?? null);

        $sticky = null;
        foreach ($home as $block) {
            if (($block['type'] ?? '') === 'button' && ($block['design'] ?? '') === 'sticky') {
                $sticky = $block;
            }
        }
        $this->assertNotNull($sticky);
        $this->assertSame('#member', $sticky['action'] ?? null);

        $map = LiffAppStudioTemplates::onboardingMap();
        $this->assertSame('rg-suite-sweets', $map['sweets'] ?? null);
        $this->assertSame('rg-suite-sweets', $map['bakery'] ?? null);
    }

    public function test_リワードホームは次特典ポイントを含む(): void
    {
        $loyalty = null;
        foreach (LiffAppStudioTemplates::catalog() as $tpl) {
            if (($tpl['id'] ?? '') === 'rg-suite-loyalty') {
                $loyalty = $tpl;
                break;
            }
        }
        $this->assertIsArray($loyalty);

        $stats = null;
        foreach ($loyalty['layouts']['home'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'stats_row') {
                $stats = $block;
                break;
            }
        }
        $this->assertIsArray($stats);
        $fields = array_column($stats['items'] ?? [], 'field');
        $this->assertContains('points_to_next', $fields);
    }
}
