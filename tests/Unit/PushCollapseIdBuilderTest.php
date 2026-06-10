<?php

namespace Tests\Unit;

use App\Support\PushCollapseIdBuilder;
use PHPUnit\Framework\TestCase;

class PushCollapseIdBuilderTest extends TestCase
{
    public function test_for_announcement_includes_project_id_and_entity_id(): void
    {
        $collapseId = PushCollapseIdBuilder::forAnnouncement(42, 100);

        $this->assertSame('42:announcement:100', $collapseId);
    }

    public function test_for_queue_called_includes_project_id_and_user_id(): void
    {
        $collapseId = PushCollapseIdBuilder::forQueueCalled(7, 55);

        $this->assertSame('7:queue_called:55', $collapseId);
    }

    public function test_for_coupon_issued_includes_project_id_and_member_coupon_id(): void
    {
        $collapseId = PushCollapseIdBuilder::forCouponIssued(3, 999);

        $this->assertSame('3:coupon_issued:999', $collapseId);
    }
}
