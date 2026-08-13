<?php

namespace Tests\Unit\Domain\Queue;

use App\Domain\Queue\Aggregates\QueueEntry;
use App\Domain\Queue\ValueObjects\QueueEntryId;
use App\Enums\QueueStatus;
use Carbon\Carbon;
use Tests\TestCase;

class QueueEntryAggregateTest extends TestCase
{
    private function createEntry(string $status = QueueStatus::WAITING): QueueEntry
    {
        return new QueueEntry(
            new QueueEntryId(1),
            1,
            $status,
            null,
            null,
        );
    }

    public function test_cancel_待ち状態をキャンセルできること(): void
    {
        $entry = $this->createEntry();

        $entry->cancel();

        $this->assertSame(QueueStatus::CANCELLED, $entry->status());
    }

    public function test_cancel_入店済みは変更しないこと(): void
    {
        $entry = $this->createEntry(QueueStatus::ENTERED);

        $entry->cancel();

        $this->assertSame(QueueStatus::ENTERED, $entry->status());
    }

    public function test_mark_as_called_待ちから呼び出しに遷移すること(): void
    {
        $entry = $this->createEntry();
        $now = Carbon::parse('2026-07-10 12:00:00');

        $entry->markAsCalled($now);

        $this->assertSame(QueueStatus::CALLED, $entry->status());
        $this->assertSame($now->getTimestamp(), $entry->calledAt()?->getTimestamp());
    }

    public function test_mark_as_entered_呼び出しから入店に遷移すること(): void
    {
        $entry = $this->createEntry(QueueStatus::CALLED);
        $now = Carbon::parse('2026-07-10 12:05:00');

        $entry->markAsEntered($now);

        $this->assertSame(QueueStatus::ENTERED, $entry->status());
        $this->assertSame($now->getTimestamp(), $entry->enteredAt()?->getTimestamp());
    }

    public function test_recall_呼び出し済みのcalled_atを更新すること(): void
    {
        $entry = $this->createEntry(QueueStatus::CALLED);
        $now = Carbon::parse('2026-07-10 12:10:00');

        $entry->recall($now);

        $this->assertSame(QueueStatus::CALLED, $entry->status());
        $this->assertSame($now->getTimestamp(), $entry->calledAt()?->getTimestamp());
    }

    public function test_recall_待ち中は拒否されること(): void
    {
        $entry = $this->createEntry(QueueStatus::WAITING);

        $this->expectException(\InvalidArgumentException::class);
        $entry->recall(Carbon::now());
    }

    public function test_apply_admin_status_waitingも設定できること(): void
    {
        $entry = $this->createEntry(QueueStatus::CALLED);

        $entry->applyAdminStatus(QueueStatus::WAITING, Carbon::now());

        $this->assertSame(QueueStatus::WAITING, $entry->status());
    }
}
