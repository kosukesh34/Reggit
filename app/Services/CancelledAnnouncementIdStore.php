<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\CancelledAnnouncementPush;
use Illuminate\Support\Carbon;

class CancelledAnnouncementIdStore
{
    private const TTL_DAYS = 7;

    private const MAX_IDS = 200;

    public function remember(int $projectId, int $announcementId, ?string $title = null): void
    {
        if ($projectId <= 0 || $announcementId <= 0) {
            return;
        }

        $announcement = Announcement::withoutGlobalScopes()->where('project_id', $projectId)->find($announcementId);
        if ($announcement?->visibility === 'recipients') {
            $title = null;
        }
        CancelledAnnouncementPush::query()->updateOrCreate(
            [
                'project_id' => $projectId,
                'announcement_id' => $announcementId,
            ],
            [
                'title' => $title !== null ? mb_substr($title, 0, 255) : null,
                'cancelled_at' => Carbon::now(),
            ]
        );

        $this->prune($projectId);
    }

    /** @return list<int> */
    public function list(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }

        $this->prune($projectId);

        return CancelledAnnouncementPush::query()
            ->where('project_id', $projectId)
            ->where('cancelled_at', '>=', Carbon::now()->subDays(self::TTL_DAYS))
            ->orderBy('announcement_id')
            ->pluck('announcement_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, title: string}>
     */
    public function listItems(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }

        $this->prune($projectId);

        return CancelledAnnouncementPush::query()
            ->where('project_id', $projectId)
            ->where('cancelled_at', '>=', Carbon::now()->subDays(self::TTL_DAYS))
            ->orderBy('announcement_id')
            ->get(['announcement_id', 'title'])
            ->map(fn (CancelledAnnouncementPush $row) => [
                'id' => (int) $row->announcement_id,
                'title' => trim((string) $row->title),
            ])
            ->values()
            ->all();
    }

    private function prune(int $projectId): void
    {
        CancelledAnnouncementPush::query()
            ->where('project_id', $projectId)
            ->where('cancelled_at', '<', Carbon::now()->subDays(self::TTL_DAYS))
            ->delete();

        $overflowIds = CancelledAnnouncementPush::query()
            ->where('project_id', $projectId)
            ->orderByDesc('cancelled_at')
            ->orderByDesc('id')
            ->skip(self::MAX_IDS)
            ->take(1000)
            ->pluck('id');

        if ($overflowIds->isNotEmpty()) {
            CancelledAnnouncementPush::query()->whereIn('id', $overflowIds)->delete();
        }
    }
}
