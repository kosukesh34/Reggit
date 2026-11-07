<?php

namespace App\View\Composers;

use App\Models\AcquisitionSource;
use App\Models\Announcement;
use App\Models\Store;
use App\Models\Survey;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\View\View;

/** 配信・会員一覧の絞り込みで選ぶお知らせ・アンケート・登録経路・店舗（ID => 表示名） */
final class SegmentOptionsComposer
{
    private const ANNOUNCEMENT_LIMIT = 100;

    public function compose(View $view): void
    {
        $projectId = AdminContext::getCurrentProjectId();
        if ($projectId === null) {
            $view->with(['segmentAnnouncementOptions' => [], 'segmentSurveyOptions' => [], 'segmentAcquisitionOptions' => [], 'segmentStoreOptions' => []]);

            return;
        }

        // お知らせ・アンケートの題名は、それを見る権限（どちらも announcements.view）がある管理者にだけ出す
        $canViewAnnouncements = auth('admin')->user()?->hasPermission('announcements.view') === true;
        $storeContext = app(StoreContext::class);
        $visibleStoreIds = $storeContext->bound ? $storeContext->visibleStoreIds : null;
        $view->with([
            'segmentAnnouncementOptions' => ! $canViewAnnouncements ? [] : Announcement::query()
                ->where('project_id', $projectId)
                ->where('delivery_status', Announcement::DELIVERY_STATUS_SENT)
                // 予約リマインドなど1人宛ての自動お知らせは数が多く、既読で絞り込む意味もないため出さない
                ->where(fn ($query) => $query->whereNull('delivery_type')->orWhere('delivery_type', '!=', Announcement::DELIVERY_TYPE_PERSONAL))
                ->orderByDesc('sent_at')
                ->orderByDesc('id')
                ->limit(self::ANNOUNCEMENT_LIMIT)
                ->get(['id', 'title', 'sent_at'])
                ->mapWithKeys(fn (Announcement $announcement): array => [
                    (int) $announcement->id => trim($announcement->sent_at?->format('Y/m/d').' '.$announcement->title),
                ])
                ->all(),
            'segmentSurveyOptions' => ! $canViewAnnouncements ? [] : Survey::query()->where('project_id', $projectId)->orderByDesc('id')->pluck('title', 'id')->all(),
            'segmentAcquisitionOptions' => AcquisitionSource::query()->where('project_id', $projectId)->orderBy('id')->pluck('name', 'id')->all(),
            // 店舗管理者には担当店舗だけを出す（null は全店舗を見られる管理者）
            'segmentStoreOptions' => Store::query()
                ->where('project_id', $projectId)
                ->when($visibleStoreIds !== null, fn ($query) => $query->whereIn('id', $visibleStoreIds ?? []))
                ->orderBy('id')
                ->pluck('name', 'id')
                ->all(),
        ]);
    }
}
