<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AnnouncementStoreRequest;
use App\Http\Requests\Admin\AnnouncementUpdateRequest;
use App\Services\Admin\AnnouncementAdminService;

class AnnouncementController extends AdminBaseController
{
    public function __construct(
        private AnnouncementAdminService $announcementAdminService
    ) {
    }

    public function announcements()
    {
        $announcements = $this->announcementAdminService->getPaginatedList(20);
        return view('admin.announcements.index', compact('announcements'));
    }

    public function createAnnouncement()
    {
        return view('admin.announcements.create');
    }

    public function storeAnnouncement(AnnouncementStoreRequest $request)
    {
        $this->announcementAdminService->create($this->resolveAdminProjectId(), $request->validated());
        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを追加しました');
    }

    public function editAnnouncement(int $id)
    {
        $announcement = $this->announcementAdminService->findOrFail($id);
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function updateAnnouncement(AnnouncementUpdateRequest $request, int $id)
    {
        $this->announcementAdminService->update($id, $request->validated());
        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを更新しました');
    }

    public function deleteAnnouncement(int $id)
    {
        $this->announcementAdminService->delete($id);
        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを削除しました');
    }
}
