<?php

namespace Tests\Unit\Domain\Member;

use App\Domain\Member\Aggregates\Member;
use App\Domain\Member\ValueObjects\MemberId;
use Carbon\Carbon;
use Tests\TestCase;

class MemberAggregateTest extends TestCase
{
    public function test_refund_used_points_for_order_利用ポイントを返却すること(): void
    {
        $member = new Member(
            new MemberId(1),
            1,
            70,
            '',
        );

        $member->refundUsedPointsForOrder(10, 30, '注文キャンセルによる返却');

        $this->assertSame(100, $member->displayPoints());
        $entries = $member->pullPendingEntries();
        $this->assertSame('refunded', $entries[0]->type);
        $this->assertSame(30, $entries[0]->signedPoints);
        $this->assertSame(10, $entries[0]->orderId);
    }

    public function test_reverse_earned_points_for_order_獲得ポイントを取り消すこと(): void
    {
        $member = new Member(
            new MemberId(1),
            1,
            110,
            '',
        );

        $member->reverseEarnedPointsForOrder(10, 10, '注文キャンセルによる取り消し');

        $this->assertSame(100, $member->displayPoints());
        $entries = $member->pullPendingEntries();
        $this->assertSame('reversed', $entries[0]->type);
        $this->assertSame(-10, $entries[0]->signedPoints);
    }

    public function test_use_points_残高から減算されること(): void
    {
        $member = new Member(
            new MemberId(1),
            1,
            100,
            '',
        );

        $member->usePoints(30, '注文で使用', 5);

        $this->assertSame(70, $member->displayPoints());
        $entries = $member->pullPendingEntries();
        $this->assertCount(1, $entries);
        $this->assertSame(-30, $entries[0]->signedPoints);
        $this->assertSame(5, $entries[0]->orderId);
    }

    public function test_use_points_残高を超える場合は利用可能分のみ減算すること(): void
    {
        $member = new Member(
            new MemberId(1),
            1,
            20,
            '',
        );

        $member->usePoints(50, '注文で使用', null);

        $this->assertSame(0, $member->displayPoints());
        $entries = $member->pullPendingEntries();
        $this->assertSame(-20, $entries[0]->signedPoints);
    }

    public function test_earn_points_正のポイントで残高が増えること(): void
    {
        $member = new Member(
            new MemberId(1),
            1,
            100,
            '',
        );

        $member->earnPoints(50, 'テスト付与', null, Carbon::parse('2026-12-31'));

        $this->assertSame(150, $member->displayPoints());
        $entries = $member->pullPendingEntries();
        $this->assertCount(1, $entries);
        $this->assertSame(50, $entries[0]->signedPoints);
    }

    public function test_earn_points_0以下は無視されること(): void
    {
        $member = new Member(
            new MemberId(1),
            1,
            100,
            '',
        );

        $member->earnPoints(0, '無視', null, Carbon::now());
        $member->earnPoints(-10, '無視', null, Carbon::now());

        $this->assertSame(100, $member->displayPoints());
        $this->assertSame([], $member->pullPendingEntries());
    }

    public function test_pull_pending_entries_取得後は空になること(): void
    {
        $member = new Member(
            new MemberId(1),
            1,
            0,
            '',
        );

        $member->earnPoints(10, '付与', null, Carbon::now());
        $member->pullPendingEntries();

        $this->assertSame([], $member->pullPendingEntries());
    }
}
