<?php

namespace App\UseCases\Announcement\Data;

use App\Models\User;

class AnnouncementMarkReadInput
{
    public function __construct(
        public User $user,
        public int $announcementId,
    ) {}
}
