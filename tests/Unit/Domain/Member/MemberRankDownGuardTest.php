<?php

namespace Tests\Unit\Domain\Member;

use App\Domain\Member\Services\MemberRankDownGuard;
use Tests\TestCase;

class MemberRankDownGuardTest extends TestCase
{
    public function test_keep_if_higher_期間内の指標が下がっても保存済みランクを維持すること(): void
    {
        $ranks = [
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 2, 'is_active' => true],
        ];
        $info = [
            'current_rank' => 'bronze',
            'current_rank_label' => 'ブロンズ',
            'current_rank_icon' => null,
            'current_rank_background' => null,
            'current_rank_color' => null,
            'next_rank' => 'gold',
            'next_rank_label' => 'ゴールド',
            'points_to_next_rank' => 80,
            'rank_basis' => 'points',
            'rank_metric' => 20,
        ];

        $kept = (new MemberRankDownGuard)->keepIfHigher($info, $ranks, 'gold');

        $this->assertSame('gold', $kept['current_rank']);
        $this->assertSame('ゴールド', $kept['current_rank_label']);
        $this->assertNull($kept['next_rank']);
    }

    public function test_keep_if_higher_保存済みより上なら上げること(): void
    {
        $ranks = [
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 2, 'is_active' => true],
        ];
        $info = [
            'current_rank' => 'gold',
            'current_rank_label' => 'ゴールド',
            'current_rank_icon' => null,
            'current_rank_background' => null,
            'next_rank' => null,
            'next_rank_label' => null,
            'points_to_next_rank' => null,
            'rank_basis' => 'points',
            'rank_metric' => 120,
        ];

        $kept = (new MemberRankDownGuard)->keepIfHigher($info, $ranks, 'bronze');

        $this->assertSame('gold', $kept['current_rank']);
    }
}
