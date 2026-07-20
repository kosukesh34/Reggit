<?php

namespace Tests\Unit\Support;

use App\Support\LiffDesignerSanitizer;
use PHPUnit\Framework\TestCase;

class LiffDesignerSanitizerSliderSourceTest extends TestCase
{
    public function test_sanitizeLayouts_sliderのsourceがgalleryのとき実データ連携として保存できること(): void
    {
        $result = LiffDesignerSanitizer::sanitizeLayouts([
            'gallery' => [[
                'type' => 'slider',
                'source' => 'gallery',
                'design' => 'hero',
                'slide_height' => 240,
                'section_label' => 'スタイルギャラリー',
                'slides' => [
                    ['image_url' => 'https://example.com/dummy.jpg', 'caption' => '無視される'],
                ],
            ]],
        ]);

        $block = $result['gallery'][0] ?? null;
        $this->assertIsArray($block);
        $this->assertSame('slider', $block['type']);
        $this->assertSame('gallery', $block['source']);
        $this->assertSame('hero', $block['design']);
        $this->assertSame(240, $block['slide_height']);
        $this->assertSame('スタイルギャラリー', $block['section_label']);
        $this->assertSame([], $block['slides']);
    }

    public function test_sanitizeLayouts_sliderの不正なsourceは無視すること(): void
    {
        $result = LiffDesignerSanitizer::sanitizeLayouts([
            'home' => [[
                'type' => 'slider',
                'source' => 'products',
                'design' => 'hero',
                'slides' => [
                    ['image_url' => 'https://example.com/a.jpg', 'caption' => 'A'],
                ],
            ]],
        ]);

        $block = $result['home'][0] ?? null;
        $this->assertIsArray($block);
        $this->assertArrayNotHasKey('source', $block);
        $this->assertCount(1, $block['slides']);
        $this->assertSame('https://example.com/a.jpg', $block['slides'][0]['image_url']);
    }
}
