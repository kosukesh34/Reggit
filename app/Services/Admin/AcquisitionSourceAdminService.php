<?php

namespace App\Services\Admin;

use App\Models\AcquisitionSource;
use Illuminate\Database\Eloquent\Collection;

/** 管理画面の流入経路の保存・読み取り。 */
class AcquisitionSourceAdminService
{
    private const RECENT_DAYS = 30;

    /** @return Collection<int, AcquisitionSource> 登録数（members_count・recent_members_count）付き */
    public function list(int $projectId): Collection
    {
        return AcquisitionSource::query()->where('project_id', $projectId)
            ->withCount(['members', 'members as recent_members_count' => fn ($query) => $query->where('created_at', '>=', now()->subDays(self::RECENT_DAYS))])
            ->latest('id')->get();
    }

    public function create(int $projectId, string $name, string $landingTab): AcquisitionSource
    {
        return AcquisitionSource::create(['project_id' => $projectId, 'name' => $name, 'code' => bin2hex(random_bytes(5)), 'landing_tab' => $landingTab]);
    }

    public function delete(int $projectId, int $sourceId): void
    {
        AcquisitionSource::query()->where('project_id', $projectId)->findOrFail($sourceId)->delete();
    }
}
