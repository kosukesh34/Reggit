<?php

namespace Tests\Unit\Support;

use App\Support\LiffDesignerSanitizer;
use PHPUnit\Framework\TestCase;

class LiffDesignerSanitizerStatsFieldTest extends TestCase
{
    public function test_stats_rowのfieldが保持されること(): void
    {
        $layouts = [
            'home' => [
                [
                    'type' => 'stats_row',
                    'section_label' => 'マイステータス',
                    'items' => [
                        ['label' => 'ポイント', 'value' => '1,240', 'hint' => 'pt', 'field' => 'points'],
                        ['label' => 'ランク', 'value' => 'ゴールド', 'hint' => '', 'field' => 'rank'],
                        ['label' => '次回特典まで', 'value' => '720', 'hint' => 'pt', 'field' => 'points_to_next'],
                        ['label' => 'クーポン', 'value' => '3', 'hint' => '枚', 'field' => 'static'],
                    ],
                ],
            ],
        ];

        $sanitized = LiffDesignerSanitizer::sanitizeLayouts($layouts);
        $items = $sanitized['home'][0]['items'];

        $this->assertSame('points', $items[0]['field']);
        $this->assertSame('rank', $items[1]['field']);
        $this->assertSame('points_to_next', $items[2]['field']);
        $this->assertArrayNotHasKey('field', $items[3]);
    }

    public function test_不正なstats_rowのfieldは落とされること(): void
    {
        $layouts = [
            'home' => [
                [
                    'type' => 'stats_row',
                    'items' => [
                        ['label' => 'ポイント', 'value' => '1', 'hint' => 'pt', 'field' => 'hack'],
                    ],
                ],
            ],
        ];

        $sanitized = LiffDesignerSanitizer::sanitizeLayouts($layouts);
        $this->assertArrayNotHasKey('field', $sanitized['home'][0]['items'][0]);
    }

    public function test_bannerのpoints_to_next_fieldが保持されること(): void
    {
        $layouts = [
            'home' => [
                [
                    'type' => 'banner',
                    'design' => 'soft',
                    'text' => '次のランクまであと 2,400 pt',
                    'field' => 'points_to_next',
                    'color' => '#007aff',
                ],
            ],
        ];

        $sanitized = LiffDesignerSanitizer::sanitizeLayouts($layouts);
        $this->assertSame('points_to_next', $sanitized['home'][0]['field']);
    }
}
