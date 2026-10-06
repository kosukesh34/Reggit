<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponDeliveryType;
use App\UseCases\Admin\Announcement\AdminAnnouncementAudiencePreviewUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementSegmentPreviewUseCase;
use App\UseCases\Admin\Coupon\AdminCouponDeliveryDetailUseCase;
use App\UseCases\Admin\Coupon\AdminCouponDeliveryPreviewMembersUseCase;
use App\UseCases\Admin\Coupon\AdminCouponDeliveryPreviewUseCase;
use App\UseCases\Admin\Member\AdminMemberSearchByKeywordUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponDeliveryController extends AdminBaseController
{
    private const PREVIEW_FIELDS = [
        'target_member_ids',
        'member_ids_input',
        'segment_period_days',
        'segment_condition',
        'segment_min_amount',
        'segment_channel',
        'segment_birthday_month',
        'segment_sex',
        'segment_visit_count_min',
        'segment_visit_count_max',
        'segment_rank',
        'segment_product_id',
        'segment_lifetime_points_min',
        'segment_lifetime_points_max',
        'segment_last_visit_mode',
        'segment_last_visit_days',
        'segment_stamp_card_id',
        'segment_stamp_count_min',
        'segment_stamp_completed',
        'segment_rfm_segments',
        'segment_tag_ids',
        'segment_birthday_months',
        'birthday_month',
        'new_member_days',
        'inactive_days',
        'target_rank',
        'stamp_card_id',
        'stamp_count_min',
        'stamp_completed',
        'days_after_last_visit',
    ];

    public function __construct(
        private AdminMemberSearchByKeywordUseCase $searchMembersUseCase,
        private AdminAnnouncementSegmentPreviewUseCase $segmentPreviewUseCase,
        private AdminAnnouncementAudiencePreviewUseCase $audiencePreviewUseCase,
        private AdminCouponDeliveryPreviewUseCase $deliveryPreviewUseCase,
        private AdminCouponDeliveryPreviewMembersUseCase $deliveryPreviewMembersUseCase,
        private AdminCouponDeliveryDetailUseCase $deliveryDetailUseCase,
    ) {}

    public function searchMembers(Request $request): JsonResponse
    {
        $projectId = $this->resolveAdminProjectId();
        $numbers = trim((string) $request->query('numbers', ''));
        if ($numbers !== '') {
            return response()->json($this->searchMembersUseCase->findByNumberList($projectId, $numbers));
        }

        $members = $this->searchMembersUseCase->handle(
            $projectId,
            (string) $request->query('q', ''),
        );

        return response()->json(['data' => $members]);
    }

    public function segmentPreview(Request $request): JsonResponse
    {
        $count = $this->segmentPreviewUseCase->handle(
            $this->resolveAdminProjectId(),
            $request->query(),
        );

        return response()->json(['count' => $count]);
    }

    public function audiencePreview(Request $request): JsonResponse
    {
        $query = $request->query();
        if (! isset($query['delivery_coupon_id']) && $request->filled('coupon_id')) {
            $query['delivery_coupon_id'] = $request->query('coupon_id');
        }

        return response()->json(
            $this->audiencePreviewUseCase->handle($this->resolveAdminProjectId(), $query),
        );
    }

    public function preview(Request $request): JsonResponse
    {
        $result = $this->deliveryPreviewUseCase->handle(
            $this->resolveAdminProjectId(),
            $this->deliveryType($request),
            $this->previewParams($request),
        );

        return response()->json($result);
    }

    public function previewMembers(Request $request): JsonResponse
    {
        $output = $this->deliveryPreviewMembersUseCase->handle(
            $this->resolveAdminProjectId(),
            $this->deliveryType($request),
            $this->previewParams($request),
        );

        return response()->json([
            'members' => $output->members,
            'total' => $output->totalCount,
            'error' => $output->error,
        ]);
    }

    public function show(string $projectKey, int $id): View
    {
        $output = $this->deliveryDetailUseCase->handle($this->resolveAdminProjectId(), $id);

        return view('admin.coupons.delivery-detail', ['view' => $output]);
    }

    private function deliveryType(Request $request): string
    {
        return (string) $request->query('delivery_type', CouponDeliveryType::IMMEDIATE_ALL);
    }

    /** @return array<string, mixed> */
    private function previewParams(Request $request): array
    {
        $params = $request->only(self::PREVIEW_FIELDS);
        if ($request->has('target_member_ids') && is_string($params['target_member_ids'] ?? null)) {
            $params['target_member_ids'] = array_values(array_filter(
                array_map('intval', explode(',', $params['target_member_ids'])),
            ));
        }

        return $params;
    }
}
