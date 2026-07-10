<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AnnouncementDeliveryStoreRequest;
use App\Http\Requests\Admin\AnnouncementStoreRequest;
use App\Http\Requests\Admin\AnnouncementUpdateRequest;
use App\UseCases\Admin\Announcement\AdminAnnouncementDeleteUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementDeliveryFormUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementEditUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementIndexUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementSaveImageUseCase;
use App\UseCases\Admin\Member\AdminMemberSearchByKeywordUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementSegmentPreviewUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementStoreDeliveryUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementStoreUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementUpdateUseCase;
use Illuminate\Http\Request;

class AnnouncementController extends AdminBaseController
{
    public function __construct(
        private AdminAnnouncementIndexUseCase $indexUseCase,
        private AdminAnnouncementStoreUseCase $storeUseCase,
        private AdminAnnouncementEditUseCase $editUseCase,
        private AdminAnnouncementUpdateUseCase $updateUseCase,
        private AdminAnnouncementDeleteUseCase $deleteUseCase,
        private AdminAnnouncementStoreDeliveryUseCase $storeDeliveryUseCase,
        private AdminAnnouncementDeliveryFormUseCase $deliveryFormUseCase,
        private AdminAnnouncementSegmentPreviewUseCase $segmentPreviewUseCase,
        private AdminMemberSearchByKeywordUseCase $searchMembersUseCase,
        private AdminAnnouncementSaveImageUseCase $saveImageUseCase,
    ) {}

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
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();

        if ($request->hasFile('image_file')) {
            $validated['image_url'] = $this->saveImageUseCase->handle($request->file('image_file'), $projectId);
        }
        unset($validated['image_file']);

        $this->storeUseCase->handle($projectId, $validated);

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを追加しました');
    }

    public function editAnnouncement(string $projectKey, int $id)
    {
        $announcement = $this->editUseCase->handle($id);

        return view('admin.announcements.edit', compact('announcement'));
    }

    public function updateAnnouncement(AnnouncementUpdateRequest $request, string $projectKey, int $id)
    {
        $this->updateUseCase->handle($id, $request->validated());

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを更新しました');
    }

    public function deleteAnnouncement(string $projectKey, int $id)
    {
        $this->deleteUseCase->handle($id);

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを削除しました');
    }

    public function createDelivery()
    {
        $form = $this->deliveryFormUseCase->handle($this->resolveAdminProjectId());

        return view('admin.announcements.delivery-create', [
            'memberCount' => $form->memberCount,
            'coupons' => $form->coupons,
        ]);
    }

    public function segmentPreview(Request $request)
    {
        $count = $this->segmentPreviewUseCase->handle(
            $this->resolveAdminProjectId(),
            (int) $request->query('period_days', 30),
            (string) $request->query('condition', 'min_amount'),
            $request->query('min_amount') !== null ? (float) $request->query('min_amount') : 0.0,
        );

        return response()->json(['count' => $count]);
    }

    public function searchMembers(Request $request)
    {
        $members = $this->searchMembersUseCase->handle(
            $this->resolveAdminProjectId(),
            (string) $request->query('q', ''),
        );

        return response()->json(['data' => $members]);
    }

    public function storeDelivery(AnnouncementDeliveryStoreRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->storeDeliveryUseCase->handle($projectId, $request->validated());

        if ($output->hasValidationError) {
            return back()
                ->withErrors([$output->validationField => $output->message])
                ->withInput();
        }

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', $output->message);
    }
}
