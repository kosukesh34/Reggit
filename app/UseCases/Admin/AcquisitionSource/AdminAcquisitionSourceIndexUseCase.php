<?php

namespace App\UseCases\Admin\AcquisitionSource;

use App\Models\AcquisitionSource;
use App\Services\Admin\AcquisitionSourceAdminService;
use App\Support\AppScreenCatalog;
use App\Support\LiffTabUrlBuilder;
use App\UseCases\Admin\AcquisitionSource\Data\AcquisitionSourceRow;

class AdminAcquisitionSourceIndexUseCase
{
    public function __construct(
        private readonly AcquisitionSourceAdminService $sources,
        private LiffTabUrlBuilder $liffTabUrls,
    ) {}

    /** @return list<AcquisitionSourceRow> */
    public function handle(int $projectId, string $projectKey): array
    {
        return $this->sources->list($projectId)->map(fn (AcquisitionSource $source) => new AcquisitionSourceRow(
            id: (int) $source->id,
            name: (string) $source->name,
            code: (string) $source->code,
            // 経路QRは選んだ画面を開き、会員登録のときに src を送る
            url: $this->liffTabUrls->build($projectId, $projectKey, (string) $source->landing_tab, ['src' => (string) $source->code]),
            landingLabel: AppScreenCatalog::tabs()[(string) $source->landing_tab] ?? 'ホーム',
            membersCount: (int) $source->members_count,
            recentMembersCount: (int) $source->recent_members_count,
        ))->values()->all();
    }
}
