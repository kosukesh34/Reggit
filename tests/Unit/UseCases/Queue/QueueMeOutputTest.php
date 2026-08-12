<?php

namespace Tests\Unit\UseCases\Queue;

use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueMeOutput;
use Tests\TestCase;

class QueueMeOutputTest extends TestCase
{
    public function test_券がないときは待ち人数を返すこと(): void
    {
        $payload = (new QueueMeOutput(null, 3, 15, shouldForgetSession: true, sessionProjectId: 1))->toArray();

        $this->assertNull($payload['queue_entry']);
        $this->assertSame('待ち番号はありません', $payload['message']);
        $this->assertSame(3, $payload['waiting_count']);
        $this->assertSame(15, $payload['estimated_wait_minutes']);
        $this->assertArrayHasKey('updated_at', $payload);
    }

    public function test_券があるときはqueue_entryを返すこと(): void
    {
        $entry = new QueueEntry(
            1,
            3,
            'A-003',
            'waiting',
            2,
            true,
            'line',
            'tok',
            '2026-01-01T00:00:00+09:00',
            '2026-01-01T00:00:00+09:00',
            null,
            3,
            1,
            10,
        );
        $payload = (new QueueMeOutput($entry, 3, 10, sessionEntryIdToRemember: 1, sessionProjectId: 1))->toArray();

        $this->assertSame(1, $payload['queue_entry']['id']);
        $this->assertArrayNotHasKey('message', $payload);
        $this->assertArrayNotHasKey('waiting_count', $payload);
        $this->assertArrayHasKey('updated_at', $payload);
    }
}
