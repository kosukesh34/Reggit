<?php

namespace App\UseCases\Admin\Coupon;

use App\Enums\CouponDeliveryType;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\StampCard;
use App\Services\Admin\MemberSegmentFormOptionsService;
use App\Services\Smaregi\SmaregiCouponSyncService;
use App\UseCases\Admin\Coupon\Data\AdminCouponBulkIssueFormOutput;

class AdminCouponBulkIssueFormUseCase
{
    public function __construct(
        private MemberSegmentFormOptionsService $segmentFormOptions,
        private SmaregiCouponSyncService $smaregiCouponSync,
    ) {}

    public function handle(int $projectId): AdminCouponBulkIssueFormOutput
    {
        $options = $this->segmentFormOptions->forProject($projectId);

        $activeCount = Coupon::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->count();
        $linkedCount = Coupon::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->whereNotNull('smaregi_coupon_id')
            ->where('smaregi_coupon_id', '!=', '')
            ->count();

        $stampCards = StampCard::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'required_stamps']);

        return new AdminCouponBulkIssueFormOutput(
            $options->coupons,
            Member::where('project_id', $projectId)->count(),
            $this->smaregiCouponSync->hasSmaregiContract($projectId),
            $linkedCount,
            $activeCount - $linkedCount,
            deliveryTypeOptions: CouponDeliveryType::getValues(),
            deliveryGroups: CouponDeliveryType::formGroups(),
            deliveryTypeJsConfig: CouponDeliveryType::jsConfig(),
            products: $options->products,
            stampCards: $stampCards,
            rankOptions: $options->rankOptions,
            defaultDeliveryType: CouponDeliveryType::IMMEDIATE_ALL,
            rfmSegments: $options->rfmSegments,
            memberTags: $options->memberTags,
            profileFields: $options->profileFields,
            audienceGroups: $options->audienceGroups,
        );
    }
}
