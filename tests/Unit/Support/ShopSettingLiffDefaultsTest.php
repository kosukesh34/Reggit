<?php

namespace Tests\Unit\Support;

use App\Support\ShopSettingLiffDefaults;
use PHPUnit\Framework\TestCase;

class ShopSettingLiffDefaultsTest extends TestCase
{
    public function test_保存済みの交換メニューproductsは注文用に置き換えること(): void
    {
        $ensured = ShopSettingLiffDefaults::ensureLiffTabLayouts([
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '交換メニュー', 'size' => 'large'],
                ['type' => 'button', 'design' => 'cta', 'text' => '特典で交換', 'action' => '#coupons'],
            ],
        ]);

        $this->assertNotEmpty($ensured['products']);
        foreach ($ensured['products'] as $block) {
            $this->assertIsArray($block);
            $this->assertNotSame('交換メニュー', $block['text'] ?? null);
            $this->assertNotSame('特典で交換', $block['text'] ?? null);
        }
    }

    public function test_カフェのproducts見出しは置き換えないこと(): void
    {
        $ensured = ShopSettingLiffDefaults::ensureLiffTabLayouts([
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ドリンク & フード', 'size' => 'large'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ],
        ]);

        $this->assertSame('ドリンク & フード', $ensured['products'][0]['text'] ?? null);
    }
}
