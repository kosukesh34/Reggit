<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AnnouncementDeliveryStoreRequest;
use App\Http\Requests\Admin\AnnouncementStoreRequest;
use App\Http\Requests\Admin\AnnouncementUpdateRequest;
use App\Jobs\SendScheduledAnnouncementJob;
use App\Models\Announcement;
use App\Models\Member;
use App\Services\PushNotificationService;
use App\UseCases\Admin\Announcement\AdminAnnouncementDeleteUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementEditUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementIndexUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementStoreUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementUpdateUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnnouncementController extends AdminBaseController
{
    private AdminAnnouncementIndexUseCase $indexUseCase;

    private AdminAnnouncementStoreUseCase $storeUseCase;

    private AdminAnnouncementEditUseCase $editUseCase;

    private AdminAnnouncementUpdateUseCase $updateUseCase;

    private AdminAnnouncementDeleteUseCase $deleteUseCase;

    private PushNotificationService $pushNotificationService;

    public function __construct(
        AdminAnnouncementIndexUseCase $indexUseCase,
        AdminAnnouncementStoreUseCase $storeUseCase,
        AdminAnnouncementEditUseCase $editUseCase,
        AdminAnnouncementUpdateUseCase $updateUseCase,
        AdminAnnouncementDeleteUseCase $deleteUseCase,
        PushNotificationService $pushNotificationService
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->storeUseCase = $storeUseCase;
        $this->editUseCase = $editUseCase;
        $this->updateUseCase = $updateUseCase;
        $this->deleteUseCase = $deleteUseCase;
        $this->pushNotificationService = $pushNotificationService;
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

    public function createDelivery()
    {
        $projectId = $this->resolveAdminProjectId();
        $memberCount = Member::where('project_id', $projectId)->count();

        return view('admin.announcements.delivery-create', compact('memberCount'));
    }

    public function searchMembers(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $q = trim((string) $request->query('q', ''));

        $query = Member::query()
            ->where('project_id', $projectId)
            ->with(['user:id,name']);

        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('member_number', 'like', '%'.$q.'%')
                    ->orWhereHas('user', function ($userQuery) use ($q) {
                        $userQuery->where('name', 'like', '%'.$q.'%');
                    });
            });
        }

        $members = $query
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function (Member $member) {
                return [
                    'id' => (int) $member->id,
                    'member_number' => (string) ($member->member_number ?? ''),
                    'name' => (string) ($member->user?->name ?? ''),
                ];
            })
            ->values();

        return response()->json(['data' => $members]);
    }

    public function storeDelivery(AnnouncementDeliveryStoreRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();
        $deliveryType = (string) $validated['delivery_type'];

        $targetMemberIds = collect($validated['target_member_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($deliveryType === Announcement::DELIVERY_TYPE_PERSONAL) {
            $validIds = Member::where('project_id', $projectId)
                ->whereIn('id', $targetMemberIds->all())
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($validIds->count() !== $targetMemberIds->count()) {
                return back()
                    ->withErrors(['target_member_ids' => '指定された会員が見つかりません'])
                    ->withInput();
            }

            $targetMemberIds = $validIds;
        } else {
            $targetMemberIds = collect();
        }

        $isScheduled = $deliveryType === Announcement::DELIVERY_TYPE_SCHEDULED;
        $scheduledAt = $isScheduled ? Carbon::parse((string) $validated['scheduled_at']) : null;
        $now = now();

        $announcement = Announcement::create([
            'project_id' => $projectId,
            'title' => (string) $validated['title'],
            'body' => $validated['body'] ?? null,
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
            'expires_at' => $validated['expires_at'] ?? null,
            'delivery_type' => $deliveryType,
            'scheduled_at' => $scheduledAt,
            'delivery_status' => $isScheduled
                ? Announcement::DELIVERY_STATUS_SCHEDULED
                : Announcement::DELIVERY_STATUS_SENT,
            'published_at' => $isScheduled ? null : $now,
            'sent_at' => $isScheduled ? null : $now,
            'target_member_ids' => $targetMemberIds->isEmpty() ? null : $targetMemberIds->all(),
        ]);

        if ($isScheduled && $scheduledAt !== null) {
            SendScheduledAnnouncementJob::dispatch($announcement->id)->delay($scheduledAt);
            $message = 'お知らせ配信を予約しました（'.$scheduledAt->format('Y/m/d H:i').'）';

            return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
                ->with('success', $message);
        }

        $result = $this->pushNotificationService->sendAnnouncement($announcement);
        $message = 'お知らせを配信しました（送信: '.$result['sent'].'件 / 失敗: '.$result['failed'].'件 / スキップ: '.$result['skipped'].'件）';

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', $message);
    }
}
