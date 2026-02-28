<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AnnouncementStoreRequest;
use App\Http\Requests\Admin\AnnouncementUpdateRequest;
use App\UseCases\Admin\Announcement\AdminAnnouncementDeleteUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementEditUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementIndexUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementStoreUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementUpdateUseCase;

class AnnouncementController extends AdminBaseController
{
    private AdminAnnouncementIndexUseCase $indexUseCase;
    private AdminAnnouncementStoreUseCase $storeUseCase;
    private AdminAnnouncementEditUseCase $editUseCase;
    private AdminAnnouncementUpdateUseCase $updateUseCase;
    private AdminAnnouncementDeleteUseCase $deleteUseCase;

    public function __construct(
        AdminAnnouncementIndexUseCase $indexUseCase,
        AdminAnnouncementStoreUseCase $storeUseCase,
        AdminAnnouncementEditUseCase $editUseCase,
        AdminAnnouncementUpdateUseCase $updateUseCase,
        AdminAnnouncementDeleteUseCase $deleteUseCase
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->storeUseCase = $storeUseCase;
        $this->editUseCase = $editUseCase;
        $this->updateUseCase = $updateUseCase;
        $this->deleteUseCase = $deleteUseCase;
    }

    public function announcements()
    {
        $announcements = $this->indexUseCase->handle();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function createAnnouncement()
    {
        return view('admin.announcements.create');
    }

    public function storeAnnouncement(AnnouncementStoreRequest $request)
    {
        $this->storeUseCase->handle($this->resolveAdminProjectId(), $request->validated());

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを追加しました');
    }

    public function editAnnouncement(int $id)
    {
        $announcement = $this->editUseCase->handle($id);

        return view('admin.announcements.edit', compact('announcement'));
    }

    public function updateAnnouncement(AnnouncementUpdateRequest $request, int $id)
    {
        $this->updateUseCase->handle($id, $request->validated());

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを更新しました');
    }

    public function deleteAnnouncement(int $id)
    {
        $this->deleteUseCase->handle($id);

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを削除しました');
    }
}
