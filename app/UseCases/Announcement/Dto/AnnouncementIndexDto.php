<?php

namespace App\UseCases\Announcement\Dto;

use Illuminate\Support\Collection;

class AnnouncementIndexDto
{
    /** @param Collection<int, \App\Models\Announcement> $announcements */
    public function __construct(public Collection $announcements)
    {
    }
}
