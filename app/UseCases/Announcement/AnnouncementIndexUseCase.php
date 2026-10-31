<?php

namespace App\UseCases\Announcement;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use App\Services\AnnouncementVisibility;
use App\UseCases\Announcement\Data\AnnouncementIndexView;
use App\Util\AdminContext;

class AnnouncementIndexUseCase
{
    public function __construct(private AnnouncementVisibility $visibility) {}

    public function handle(?User $user): AnnouncementIndexView
    {
        $projectId = AdminContext::requireCurrentProjectId();

        $announcements = $this->visibility->apply(Announcement::where('project_id', $projectId), $user)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->orderBy('is_pinned', 'desc')
            ->orderBy('published_at', 'desc')
            ->take(10)
            ->get(['id', 'title', 'body', 'action_path', 'action_label', 'image_url', 'image_paths', 'project_id', 'private_image_path', 'published_at', 'is_pinned']);

        $readAtByAnnouncement = collect();
        if ($user !== null && $announcements->isNotEmpty()) {
            $readAtByAnnouncement = AnnouncementRead::where('project_id', $projectId)
                ->where('user_id', $user->id)
                ->whereIn('announcement_id', $announcements->pluck('id')->all())
                ->pluck('read_at', 'announcement_id');
        }

        $rows = $announcements->map(function (Announcement $announcement) use ($readAtByAnnouncement) {
            $readAt = $readAtByAnnouncement->get($announcement->id);

            return [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'action_path' => $announcement->action_path,
                'action_label' => $announcement->action_label,
                'image_urls' => $announcement->imageUrls(),
                'image_url' => $announcement->image_url,
                'published_at' => $announcement->published_at?->toIso8601String(),
                'is_pinned' => (bool) $announcement->is_pinned,
                'is_read' => $readAt !== null,
                'read_at' => $readAt,
            ];
        });

        return new AnnouncementIndexView($rows);
    }
}
