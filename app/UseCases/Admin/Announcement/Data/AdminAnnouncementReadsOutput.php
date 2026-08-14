<?php

namespace App\UseCases\Admin\Announcement\Data;

use App\Models\Announcement;

class AdminAnnouncementReadsOutput
{
    /**
     * @param  list<array{member_id: int, member_name: string, member_number: string, channel: string, read_at: string|null, coupon_status: string, coupon_used_at: string|null}>  $recipients
     */
    public function __construct(
        public Announcement $announcement,
        public array $recipients,
        public int $targetCount,
        public int $readCount,
        public int $couponIssuedCount,
        public int $couponUsedCount,
    ) {}
}
