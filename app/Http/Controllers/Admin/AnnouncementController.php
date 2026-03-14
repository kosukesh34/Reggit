<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AnnouncementDeliveryStoreRequest;
use App\Http\Requests\Admin\AnnouncementStoreRequest;
use App\Http\Requests\Admin\AnnouncementUpdateRequest;
use App\Services\Admin\MemberAdminService;
use App\Services\Admin\SalesSegmentService;
use App\UseCases\Admin\Announcement\AdminAnnouncementDeleteUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementEditUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementIndexUseCase;
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
        private SalesSegmentService $salesSegmentService,
        private MemberAdminService $memberAdminService
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
        $this->storeUseCase->handle($this->resolveAdminProjectId(), $request->validated());

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
        $projectId = $this->resolveAdminProjectId();
        $memberCount = $this->memberAdminService->getCountByProject($projectId);
        $coupons = $this->memberAdminService->getActiveCouponsForSelect($projectId);

        return view('admin.announcements.delivery-create', compact('memberCount', 'coupons'));
    }

    public function segmentPreview(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $periodDays = max(1, min(365, (int) $request->query('period_days', 30)));
        $condition = (string) $request->query('condition', 'min_amount');
        $minAmount = $request->query('min_amount') !== null ? (float) $request->query('min_amount') : 0.0;
        $noOrdersOnly = $condition === 'no_orders';

        $memberIds = $this->salesSegmentService->getMemberIdsBySalesCriteria(
            $projectId,
            $periodDays,
            $noOrdersOnly ? null : $minAmount,
            $noOrdersOnly
        );

        return response()->json(['count' => $memberIds->count()]);
    }

    public function searchMembers(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $keyword = trim((string) $request->query('q', ''));
        $members = $this->memberAdminService->searchByKeyword($projectId, $keyword);

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
