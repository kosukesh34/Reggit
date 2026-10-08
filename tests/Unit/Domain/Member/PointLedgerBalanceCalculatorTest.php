<?php

namespace Tests\Unit\Domain\Member;

use App\Domain\Member\Services\PointLedgerBalanceCalculator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PointLedgerBalanceCalculatorTest extends TestCase
{
    private function entry(string $type, int $points, string $createdAt, ?string $expiry = null, ?int $orderId = null): array
    {
        return ['type' => $type, 'points' => $points, 'created_at' => $createdAt, 'expires_at' => $expiry, 'order_id' => $orderId];
    }

    public function test_spending_earliest_expiry_does_not_expire_the_other_grant(): void
    {
        $transactions = [
            $this->entry('earned', 100, '2026-10-01', '2026-10-10'),
            $this->entry('earned', 200, '2026-10-02', '2026-10-30'),
            $this->entry('used', -80, '2026-10-03'),
        ];
        $calculator = new PointLedgerBalanceCalculator;
        $before = $calculator->calculate($transactions, new DateTimeImmutable('2026-10-10 23:59:58'));
        $this->assertSame(220, $before['balance']);
        $this->assertSame(20, $before['points_expiring']);
        $after = $calculator->calculate($transactions, new DateTimeImmutable('2026-10-11'));
        $this->assertSame(200, $after['balance']);
        $this->assertSame(200, $after['points_expiring']);
    }

    public function test_refunding_does_not_extend_the_original_expiry(): void
    {
        $transactions = [
            $this->entry('earned', 100, '2026-10-01', '2026-10-10'),
            $this->entry('used', -100, '2026-10-03', orderId: 7),
            $this->entry('refunded', 100, '2026-10-11', orderId: 7),
        ];
        $this->assertSame(0, (new PointLedgerBalanceCalculator)->calculate($transactions, new DateTimeImmutable('2026-10-12'))['balance']);
    }

    public function test_spending_after_one_grant_expires_uses_only_the_live_grant(): void
    {
        $transactions = [
            $this->entry('earned', 100, '2026-10-01', '2026-10-05'),
            $this->entry('earned', 200, '2026-10-02'),
            $this->entry('used', -50, '2026-10-07'),
        ];
        $result = (new PointLedgerBalanceCalculator)->calculate($transactions, new DateTimeImmutable('2026-10-08'));
        $this->assertSame(150, $result['balance']);
        $this->assertNull($result['expires_at']);
    }

    public function test_partial_refunds_preserve_expiry_for_the_remaining_refund(): void
    {
        $transactions = [
            $this->entry('earned', 100, '2026-10-01', '2026-10-10'),
            $this->entry('used', -100, '2026-10-03', orderId: 7),
            $this->entry('refunded', 40, '2026-10-04', orderId: 7),
            $this->entry('refunded', 60, '2026-10-11', orderId: 7),
        ];
        $this->assertSame(0, (new PointLedgerBalanceCalculator)->calculate($transactions, new DateTimeImmutable('2026-10-12'))['balance']);
    }
}
