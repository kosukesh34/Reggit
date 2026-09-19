<?php

namespace App\Observers;

use App\Events\ProjectDataUpdated;
use App\Models\Project;
use App\Util\LogHelper;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

class ProjectDataRealtimeObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Model $model): void
    {
        $this->broadcast($model);
    }

    public function deleted(Model $model): void
    {
        $this->broadcast($model);
    }

    private function broadcast(Model $model): void
    {
        $projectId = (int) ($model->getAttribute('project_id') ?? 0);
        if ($projectId <= 0) {
            return;
        }

        $projectKey = (string) Project::query()->whereKey($projectId)->value('project_key');
        if ($projectKey === '') {
            return;
        }

        $topic = match (class_basename($model)) {
            'QueueEntry' => 'queue',
            'Order', 'OrderItem' => 'orders',
            'Coupon', 'MemberCoupon', 'CouponRedemption' => 'coupons',
            'MemberStamp', 'MemberStampTransaction' => 'stamps',
            'PointTransaction' => 'points',
            'Product', 'ProductOption', 'ProductOptionGroup' => 'products',
            'Reservation' => 'reservations',
            'Announcement' => 'announcements',
            'GalleryItem', 'GalleryCategory' => 'gallery',
            default => 'all',
        };

        try {
            event(new ProjectDataUpdated($projectKey, $topic));
        } catch (\Throwable $e) {
            // WebSocket基盤の一時停止で本来の保存処理まで失敗させない。
            LogHelper::warning('[Realtime] project data broadcast failed', [
                'project_id' => $projectId,
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
