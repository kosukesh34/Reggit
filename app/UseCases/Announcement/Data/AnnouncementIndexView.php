<?php

namespace App\UseCases\Announcement\Data;

use Illuminate\Support\Collection;

/** お知らせ一覧画面用の表示データ。 @param Collection<int, \App\Models\Announcement> $announcements */
class AnnouncementIndexView
{
    public function __construct(public Collection $announcements)
    {
    }
}
