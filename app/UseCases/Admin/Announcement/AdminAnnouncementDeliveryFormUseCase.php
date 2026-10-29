<?php

namespace App\UseCases\Admin\Announcement;

use App\Domain\Settings\Repositories\LineConnectionSettings;
use App\Domain\Settings\Repositories\ProfileSettings;
use App\Models\Product;
use App\Models\Project;
use App\Services\Admin\CouponAdminService;
use App\Services\Admin\MemberAdminService;
use App\Services\Admin\MemberTagAdminService;
use App\Services\Admin\RfmAnalysisService;
use App\Services\Line\LineService;
use App\UseCases\Admin\Announcement\Data\AdminAnnouncementDeliveryFormView;

class AdminAnnouncementDeliveryFormUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
        private CouponAdminService $couponAdminService,
        private MemberTagAdminService $memberTagAdminService,
        private RfmAnalysisService $rfmAnalysisService,
        private ProfileSettings $profileSettings,
        private AdminMemberAudienceGroupUseCase $audienceGroupUseCase,
        private LineService $lineService,
        private LineConnectionSettings $lineSettings,
    ) {}

    public function handle(int $projectId): AdminAnnouncementDeliveryFormView
    {
        $products = Product::query()
            ->where('project_id', $projectId)
            ->orderBy('name')
            ->get(['id', 'name', 'smaregi_product_id']);
        $linePlanEnabled = (bool) Project::query()->whereKey($projectId)->value('line_plan_enabled');

        return new AdminAnnouncementDeliveryFormView(
            $this->memberAdminService->getCountByProject($projectId),
            $this->couponAdminService->getActiveCouponsForSelect($projectId),
            $products,
            $this->memberTagAdminService->getListByProject($projectId),
            $this->rfmAnalysisService->segmentOptions(),
            array_values($this->profileSettings->getProfileFieldSettings($projectId)['custom_fields'] ?? []),
            $this->audienceGroupUseCase->list($projectId),
            $linePlanEnabled,
            // 送信時と同じく、店舗のトークンが無ければ共通設定のトークンで数える
            $linePlanEnabled ? $this->lineService->getMessageQuota($this->lineSettings->getLineChannelAccessToken($projectId)) : null,
        );
    }
}
