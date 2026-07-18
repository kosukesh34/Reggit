<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AnnouncementDeliveryStoreRequest;
use App\Http\Requests\Admin\AnnouncementStoreRequest;
use App\Http\Requests\Admin\AnnouncementUpdateRequest;
use App\UseCases\Admin\Announcement\AdminAnnouncementDeleteUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementDeliveryFormUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementEditUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementIndexUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementReadsUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementSaveImageUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementSegmentPreviewUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementStoreDeliveryUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementStoreUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementUpdateUseCase;
use App\UseCases\Admin\Member\AdminMemberSearchByKeywordUseCase;
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
        private AdminAnnouncementReadsUseCase $readsUseCase,
    ) {}

    public function announcements(Request $request)
    {
        $output = $this->indexUseCase->handle([
            'delivery_type' => $request->query('delivery_type'),
            'keyword' => $request->query('keyword'),
            'published_from' => $request->query('published_from'),
            'published_to' => $request->query('published_to'),
        ]);
        $output->announcements->appends($request->query());

        return view('admin.announcements.index', [
            'announcements' => $output->announcements,
            'filters' => $output->filters,
            'deliveryTypeCounts' => $output->deliveryTypeCounts,
            'deliveryTypeLabels' => $output->deliveryTypeLabels,
            'deliveryStatusLabels' => $output->deliveryStatusLabels,
        ]);
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

        $validated['is_pinned'] = (bool) ($validated['is_active'] ?? false);
        unset($validated['is_active']);
        if (! isset($validated['published_at']) || $validated['published_at'] === null || $validated['published_at'] === '') {
            $validated['published_at'] = now();
        }

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
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();

        if ($request->hasFile('image_file')) {
            $validated['image_url'] = $this->saveImageUseCase->handle($request->file('image_file'), $projectId);
        }
        unset($validated['image_file']);

        $this->updateUseCase->handle($id, $validated);

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを更新しました');
    }

    public function deleteAnnouncement(string $projectKey, int $id)
    {
        $this->deleteUseCase->handle($id);

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを削除しました');
    }

    public function announcementReads(string $projectKey, int $id)
    {
        $output = $this->readsUseCase->handle($id);

        return view('admin.announcements.reads', [
            'announcement' => $output->announcement,
            'reads' => $output->reads,
            'readCount' => $output->readCount,
        ]);
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
            $request,
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
        $validated = $request->validated();

        if ($request->hasFile('image_file')) {
            $validated['image_url'] = $this->saveImageUseCase->handle($request->file('image_file'), $projectId);
        }
        unset($validated['image_file']);

        $output = $this->storeDeliveryUseCase->handle($projectId, $validated);

        if ($output->hasValidationError) {
            return back()
                ->withErrors([$output->validationField => $output->message])
                ->withInput();
        }

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', $output->message);
    }
}
