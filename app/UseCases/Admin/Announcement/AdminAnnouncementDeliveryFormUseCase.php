<?php

namespace App\UseCases\Admin\Announcement;

use App\Domain\Settings\Repositories\LineConnectionSettings;
use App\Models\Project;
use App\Services\Admin\MemberAdminQueryService;
use App\Services\Admin\MemberSegmentFormOptionsService;
use App\Services\Line\LineService;
use App\UseCases\Admin\Announcement\Data\AdminAnnouncementDeliveryFormView;

class AdminAnnouncementDeliveryFormUseCase
{
    public function __construct(
        private MemberAdminQueryService $memberAdminService,
        private MemberSegmentFormOptionsService $segmentFormOptions,
        private LineService $lineService,
        private LineConnectionSettings $lineSettings,
    ) {}

    public function handle(int $projectId): AdminAnnouncementDeliveryFormView
    {
        $options = $this->segmentFormOptions->forProject($projectId);
        $linePlanEnabled = (bool) Project::query()->whereKey($projectId)->value('line_plan_enabled');

        return new AdminAnnouncementDeliveryFormView(
            $this->memberAdminService->getCountByProject($projectId),
            $options->coupons,
            $options->products,
            $options->memberTags,
            $options->rfmSegments,
            $options->profileFields,
            $options->audienceGroups,
            $linePlanEnabled,
            // 送信時と同じく、店舗のトークンが無ければ共通設定のトークンで数える
            $linePlanEnabled ? $this->lineService->getMessageQuota($this->lineSettings->getLineChannelAccessToken($projectId)) : null,
        );
    }
}
