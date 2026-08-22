<?php

namespace Tests\Unit\Domain\Member;

use App\Domain\Member\Services\MemberRankResolver;
use Tests\TestCase;

class MemberRankResolverTest extends TestCase
{
    public function test_resolve_ポイントに応じたランクを返すこと(): void
    {
        $ranks = [
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'silver', 'label' => 'シルバー', 'min_points' => 100, 'order' => 2, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 500, 'order' => 3, 'is_active' => true],
        ];

        $info = (new MemberRankResolver)->resolve($ranks, 'points', 150, 0, 'bronze');

        $this->assertSame('silver', $info['current_rank']);
        $this->assertSame('ゴールド', $info['next_rank_label']);
        $this->assertSame(350, $info['points_to_next_rank']);
    }

    public function test_resolve_同一min_pointsならorderが大きい方が選ばれること(): void
    {
        $ranks = [
            ['key' => 'guest', 'label' => 'ゲスト', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 2, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 3, 'is_active' => true],
        ];

        $info = (new MemberRankResolver)->resolve($ranks, 'points', 0, 0, null);

        $this->assertSame('bronze', $info['current_rank']);
    }
}
