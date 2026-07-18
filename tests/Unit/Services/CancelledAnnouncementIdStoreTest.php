<?php

namespace Tests\Unit\Services;

use App\Services\CancelledAnnouncementIdStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelledAnnouncementIdStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_remember_and_list_returns_unique_ids(): void
    {
        $store = new CancelledAnnouncementIdStore();
        $store->remember(1, 10, 'A');
        $store->remember(1, 20, 'B');
        $store->remember(1, 10, 'A2');

        $this->assertSame([10, 20], $store->list(1));
        $this->assertSame([], $store->list(2));
        $this->assertSame([
            ['id' => 10, 'title' => 'A2'],
            ['id' => 20, 'title' => 'B'],
        ], $store->listItems(1));
    }
}
