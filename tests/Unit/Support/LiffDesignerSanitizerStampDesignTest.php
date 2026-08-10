<?php

namespace Tests\Unit\Support;

use App\Support\LiffDesignerSanitizer;
use PHPUnit\Framework\TestCase;

class LiffDesignerSanitizerStampDesignTest extends TestCase
{
    public function test_sanitize_tab_settings_スタンプと会員証のデザイン設定を許可すること(): void
    {
        $result = LiffDesignerSanitizer::sanitizeTabSettings([
            '_global' => [
                'member_card_accent_color' => '#123456',
                'member_card_bg_style' => 'gradient',
                'member_card_gloss' => 'mirror',
                'member_card_surface' => 'metal',
                'coupon_screen_design' => 'gallery',
                'coupon_media_style' => 'icon',
                'coupon_show_available' => true,
                'coupon_show_history' => false,
                'products_group_by_category' => false,
                'stamp_screen_design' => 'wallet',
                'stamp_slot_style' => 'rounded',
                'stamp_filled_style' => 'image',
                'stamp_accent_color' => '#abcdef',
            ],
        ]);

        $this->assertNotNull($result);
        $global = $result['_global'] ?? [];
        $this->assertSame('#123456', $global['member_card_accent_color']);
        $this->assertSame('gradient', $global['member_card_bg_style']);
        $this->assertSame('mirror', $global['member_card_gloss']);
        $this->assertSame('metal', $global['member_card_surface']);
        $this->assertSame('gallery', $global['coupon_screen_design']);
        $this->assertSame('icon', $global['coupon_media_style']);
        $this->assertTrue($global['coupon_show_available']);
        $this->assertFalse($global['coupon_show_history']);
        $this->assertFalse($global['products_group_by_category']);
        $this->assertSame('wallet', $global['stamp_screen_design']);
        $this->assertSame('rounded', $global['stamp_slot_style']);
        $this->assertSame('image', $global['stamp_filled_style']);
        $this->assertSame('#abcdef', $global['stamp_accent_color']);
    }

    public function test_sanitize_layouts_dataスタンプブロックの上書き設定を許可すること(): void
    {
        $result = LiffDesignerSanitizer::sanitizeLayouts([
            'stamps' => [[
                'type' => 'data',
                'source' => 'stamps',
                'design' => 'premium',
                'stamp_design' => 'minimal',
                'stamp_slot_style' => 'square',
                'stamp_filled_style' => 'solid',
                'stamp_accent_color' => '#222222',
            ]],
        ]);

        $block = $result['stamps'][0] ?? null;
        $this->assertNotNull($block);
        $this->assertSame('stamps', $block['source']);
        $this->assertSame('minimal', $block['stamp_design']);
        $this->assertSame('square', $block['stamp_slot_style']);
        $this->assertSame('solid', $block['stamp_filled_style']);
        $this->assertSame('#222222', $block['stamp_accent_color']);
    }
}
