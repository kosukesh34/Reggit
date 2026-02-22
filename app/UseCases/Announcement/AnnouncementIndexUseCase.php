<?php

namespace App\UseCases\Announcement;

use App\Models\Announcement;
use App\UseCases\Announcement\Data\AnnouncementIndexView;
use App\Util\AdminContext;

class AnnouncementIndexUseCase
{
    public function handle(): AnnouncementIndexView
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;

        $announcements = Announcement::where('project_id', $projectId)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->orderBy('is_pinned', 'desc')
            ->orderBy('published_at', 'desc')
            ->take(10)
            ->get(['id', 'title', 'body', 'published_at', 'is_pinned']);

        return new AnnouncementIndexView($announcements);
    }
}
