<?php

namespace Tests\Unit\Domain\Reservation;

use App\Domain\Reservation\Policies\ReservationReminderPolicy;
use App\Domain\Reservation\ValueObjects\ReservationReminderCandidate;
use App\Domain\Reservation\ValueObjects\ReservationReminderConfig;
use PHPUnit\Framework\TestCase;

class ReservationReminderPolicyTest extends TestCase
{
    private function candidate(string $reservedAt, string $createdAt, ?string $store = '渋谷店', ?string $course = null): ReservationReminderCandidate
    {
        return new ReservationReminderCandidate(1, 1, 1, $store, 10, new \DateTimeImmutable($reservedAt), new \DateTimeImmutable($createdAt), 2, $course);
    }

    public function test_設定時間前に入った予約だけを送ること(): void
    {
        $policy = new ReservationReminderPolicy;
        $now = new \DateTimeImmutable('2026-10-27 12:00');

        $this->assertTrue($policy->isDue($this->candidate('2026-10-28 11:00', '2026-10-20 10:00'), $now, 24));
        // まだ24時間より前
        $this->assertFalse($policy->isDue($this->candidate('2026-10-28 13:00', '2026-10-20 10:00'), $now, 24));
        // 来店の24時間前より後に入った予約
        $this->assertFalse($policy->isDue($this->candidate('2026-10-28 11:00', '2026-10-27 11:30'), $now, 24));
        // 過ぎた予約と、送らない設定
        $this->assertFalse($policy->isDue($this->candidate('2026-10-27 11:00', '2026-10-20 10:00'), $now, 24));
        $this->assertFalse($policy->isDue($this->candidate('2026-10-28 11:00', '2026-10-20 10:00'), $now, 0));
    }

    public function test_店舗と人数とコースを本文に入れること(): void
    {
        $policy = new ReservationReminderPolicy;

        $this->assertSame('渋谷店で10月28日 18:30からご予約をいただいています（2名・季節のコース）。ご来店をお待ちしております。', $policy->message($this->candidate('2026-10-28 18:30', '2026-10-20', '渋谷店', '季節のコース')));
        $this->assertSame('10月28日 18:30からご予約をいただいています（2名）。ご来店をお待ちしております。', $policy->message($this->candidate('2026-10-28 18:30', '2026-10-20', null)));
    }

    public function test_1回目と2回目を時期ごとに選び送信済みの回は除くこと(): void
    {
        $policy = new ReservationReminderPolicy;
        $config = new ReservationReminderConfig(24, 3, null);
        $reservation = fn (bool $first = false, bool $second = false) => new ReservationReminderCandidate(1, 1, 1, null, 10, new \DateTimeImmutable('2026-10-28 18:00'), new \DateTimeImmutable('2026-10-20'), 2, null, $first, $second);

        $this->assertSame(['first'], $policy->dueStages($reservation(), new \DateTimeImmutable('2026-10-27 20:00'), $config));
        $this->assertSame(['second'], $policy->dueStages($reservation(first: true), new \DateTimeImmutable('2026-10-28 15:30'), $config));
        // 止まっていて両方の時期が来ていたら、両方まとめて1通にする
        $this->assertSame(['first', 'second'], $policy->dueStages($reservation(), new \DateTimeImmutable('2026-10-28 15:30'), $config));
        $this->assertSame([], $policy->dueStages($reservation(true, true), new \DateTimeImmutable('2026-10-28 15:30'), $config));
    }

    public function test_本文の差し込みを置き換えること(): void
    {
        $candidate = $this->candidate('2026-10-28 18:30', '2026-10-20', '渋谷店', '季節のコース');

        $this->assertSame('渋谷店 10月28日 18:30 2名 季節のコース（2名・季節のコース）', (new ReservationReminderPolicy)->message($candidate, '{店舗} {日時} {人数} {コース}{詳細}'));
    }
}
