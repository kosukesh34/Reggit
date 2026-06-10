<?php

namespace App\UseCases\Announcement\Data;

use App\Models\User;

class AnnouncementIndexInput
{
    public function __construct(
        public ?User $user,
    ) {}
}
