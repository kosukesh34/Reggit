<?php

namespace App\UseCases\Admin\Announcement\Data;

use App\Models\Announcement;

class AdminAnnouncementReadsOutput
{
    /**
     * @param  list<array{member_name: string, member_number: string, read_at: string}>  $reads
     */
    public function __construct(
        public Announcement $announcement,
        public array $reads,
        public int $readCount,
    ) {}
}
