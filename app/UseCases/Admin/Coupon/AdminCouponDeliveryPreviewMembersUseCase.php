<?php

namespace App\UseCases\Admin\Coupon;

use App\Models\Member;
use App\Services\Admin\CouponDeliveryTargetService;
use App\Support\AdminMemberIdentifier;
use App\UseCases\Admin\Coupon\Data\AdminCouponDeliveryPreviewMembersOutput;

class AdminCouponDeliveryPreviewMembersUseCase
{
    private const LIMIT = 100;

    public function __construct(
        private CouponDeliveryTargetService $couponDeliveryTargetService,
        private AdminMemberIdentifier $memberIdentifier,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function handle(int $projectId, string $deliveryType, array $params): AdminCouponDeliveryPreviewMembersOutput
    {
        $resolved = $this->couponDeliveryTargetService->resolve($projectId, $deliveryType, $params);
        if ($resolved['error'] !== null) {
            return new AdminCouponDeliveryPreviewMembersOutput([], 0, $resolved['error']);
        }

        $ids = $resolved['member_ids'];
        $total = count($ids);
        $limitedIds = array_slice($ids, 0, self::LIMIT);
        $members = [];
        if ($limitedIds !== []) {
            $byId = Member::query()
                ->where('project_id', $projectId)
                ->whereIn('id', $limitedIds)
                ->with('user')
                ->get()
                ->keyBy('id');
            foreach ($limitedIds as $id) {
                $member = $byId->get($id);
                $user = $member?->user;
                $members[] = [
                    'member_id' => $id,
                    'member_name' => $user !== null && trim((string) $user->name) !== ''
                        ? (string) $user->name
                        : '—',
                    'display_identifier' => $this->memberIdentifier->value($member),
                    'member_number' => $member !== null && trim((string) $member->member_number) !== ''
                            ? (string) $member->member_number
                            : '—',
                ];
            }
        }

        return new AdminCouponDeliveryPreviewMembersOutput($members, $total, null);
    }
}
