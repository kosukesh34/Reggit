<?php

namespace App\UseCases\Announcement;

use App\Models\AdminUser;
use App\Models\Announcement;
use App\Models\User;
use App\Services\AnnouncementVisibility;
use Closure;

/** 非公開画像つきお知らせを、見てよい人（配信対象の会員・担当店舗の管理者）に限って探す。 */
class AnnouncementPrivateImageUseCase
{
    public function __construct(private AnnouncementVisibility $visibility) {}

    /** @param Closure(): ?User $currentUser 管理者でないときだけ会員を特定する */
    public function find(int $projectId, int $announcementId, ?AdminUser $admin, Closure $currentUser): Announcement
    {
        $query = Announcement::where('project_id', $projectId);
        if ($admin === null || ! $admin->canAccessProject($projectId)) {
            $query = $this->visibility->apply($query, $currentUser());
            $query->whereNotNull('published_at')->where('published_at', '<=', now())
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
        } elseif (! $admin->isSystemAdmin() && ! $admin->isProjectAdmin()) {
            $allowed = $admin->stores()->where('stores.project_id', $projectId)->pluck('stores.id')->all();
            $query->where(function ($q) use ($allowed) {
                $q->whereRaw('1 = 0');
                foreach ($allowed as $storeId) {
                    $q->orWhereJsonContains('store_ids', (int) $storeId);
                }
            });
        }

        return $query->findOrFail($announcementId);
    }

    /** 署名つき URL で開く画像。署名の確認は呼び出し側で行う */
    public function findForSignedMedia(int $announcementId): Announcement
    {
        return Announcement::withoutGlobalScopes()->findOrFail($announcementId);
    }
}
