<?php

namespace App\UseCases\Admin\Coupon;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\Product;
use App\Models\StampCard;
use App\Services\Admin\CouponAdminService;
use App\Services\Smaregi\SmaregiCouponSyncService;
use App\UseCases\Admin\Coupon\Data\AdminCouponBulkIssueFormOutput;
use Illuminate\Support\Collection;

class AdminCouponBulkIssueFormUseCase
{
    private const COUPON_PICKER_PER_PAGE = 10;

    public function __construct(
        private CouponAdminService $couponAdminService,
        private SmaregiCouponSyncService $smaregiCouponSync,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId, string $couponSearch = '', ?int $selectedCouponId = null): AdminCouponBulkIssueFormOutput
    {
        $coupons = $this->couponAdminService->getActiveCouponsForSelectPaginated(
            $projectId,
            $couponSearch,
            self::COUPON_PICKER_PER_PAGE,
        );

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

        $memberCount = Member::where('project_id', $projectId)->count();

        $scheduledDeliveries = CouponDelivery::query()
            ->where('project_id', $projectId)
            ->where('status', CouponDeliveryStatus::SCHEDULED)
            ->with('coupon')
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        $recentDeliveries = CouponDelivery::query()
            ->where('project_id', $projectId)
            ->with('coupon')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $deliveryTypeOptions = CouponDeliveryType::getValues();
        $deliveryHistories = $this->buildDeliveryHistories($recentDeliveries, $deliveryTypeOptions);
        $deliveryCountByCouponId = $this->countDeliveriesByCouponId($projectId);

        $products = Product::query()
            ->where('project_id', $projectId)
            ->orderBy('name')
            ->get(['id', 'name', 'smaregi_product_id']);

        $stampCards = StampCard::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'required_stamps']);

        $rankOptions = [];
        foreach ($this->shopSettingRepository->getMemberRankSettings($projectId) as $rank) {
            if (($rank['is_active'] ?? true) !== true) {
                continue;
            }
            $rankOptions[] = [
                'key' => (string) $rank['key'],
                'label' => (string) ($rank['label'] ?? $rank['key']),
            ];
        }

        $selectedCoupon = null;
        if ($selectedCouponId !== null && $selectedCouponId > 0) {
            $selectedCoupon = Coupon::query()
                ->where('project_id', $projectId)
                ->where('is_active', true)
                ->whereKey($selectedCouponId)
                ->first();
        }

        return new AdminCouponBulkIssueFormOutput(
            $coupons,
            $memberCount,
            $this->smaregiCouponSync->hasSmaregiContract($projectId),
            $linkedCount,
            $activeCount - $linkedCount,
            $scheduledDeliveries,
            $deliveryHistories,
            $deliveryTypeOptions,
            CouponDeliveryType::formGroups(),
            CouponDeliveryType::jsConfig(),
            $products,
            $deliveryCountByCouponId,
            $stampCards,
            $rankOptions,
            $couponSearch,
            $selectedCoupon,
            CouponDeliveryType::IMMEDIATE_ALL,
        );
    }

    /**
     * @param  Collection<int, CouponDelivery>  $deliveries
     * @param  array<string, string>  $deliveryTypeOptions
     * @return list<array{
     *     coupon_name: string,
     *     coupon_code: string,
     *     delivery_type_label: string,
     *     status: string,
     *     status_label: string,
     *     status_badge: string,
     *     timing_label: string,
     *     issued_count_label: string,
     *     recipients_label: string,
     *     recipients_title: string,
     *     id: int
     * }>
     */
    private function buildDeliveryHistories(Collection $deliveries, array $deliveryTypeOptions): array
    {
        $statusLabels = CouponDeliveryStatus::getValues();
        $memberLabels = $this->resolveMemberLabels($deliveries);
        $rows = [];
        foreach ($deliveries as $delivery) {
            $status = (string) $delivery->status;
            $recipients = $this->formatRecipients($delivery, $memberLabels);
            $rows[] = [
                'id' => (int) $delivery->id,
                'coupon_name' => (string) ($delivery->coupon?->name ?: $delivery->coupon?->code ?: '—'),
                'coupon_code' => (string) ($delivery->coupon?->code ?: '—'),
                'delivery_type_label' => $deliveryTypeOptions[$delivery->delivery_type] ?? (string) $delivery->delivery_type,
                'status' => $status,
                'status_label' => $statusLabels[$status] ?? $status,
                'status_badge' => $this->statusBadgeClass($status),
                'timing_label' => $this->formatTimingLabel($delivery),
                'issued_count_label' => number_format((int) $delivery->issued_count).'人',
                'recipients_label' => $recipients['label'],
                'recipients_title' => $recipients['title'],
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, CouponDelivery>  $deliveries
     * @return array<int, string>
     */
    private function resolveMemberLabels(Collection $deliveries): array
    {
        $ids = [];
        foreach ($deliveries as $delivery) {
            foreach ((array) ($delivery->target_member_ids ?? []) as $rawId) {
                $id = (int) $rawId;
                if ($id > 0) {
                    $ids[$id] = true;
                }
            }
        }
        if ($ids === []) {
            return [];
        }

        $first = $deliveries->first();
        $projectId = $first !== null ? (int) $first->project_id : 0;
        $query = Member::query()->whereIn('id', array_keys($ids))->with('user');
        if ($projectId > 0) {
            $query->where('project_id', $projectId);
        }

        $labels = [];
        foreach ($query->get() as $member) {
            $name = $member->user !== null && trim((string) $member->user->name) !== ''
                ? (string) $member->user->name
                : '—';
            $number = trim((string) $member->member_number);
            $labels[(int) $member->id] = $number !== '' ? $name.'（'.$number.'）' : $name;
        }

        return $labels;
    }

    /**
     * @param  array<int, string>  $memberLabels
     * @return array{label: string, title: string}
     */
    private function formatRecipients(CouponDelivery $delivery, array $memberLabels): array
    {
        if (CouponDeliveryType::isEventRankUpArrival((string) $delivery->delivery_type)) {
            $rank = trim((string) (($delivery->segment_criteria['rank'] ?? '')));
            $label = $rank !== ''
                ? '到達ランク: '.$rank.'（昇格時に自動発行）'
                : '昇格時に自動発行';
            $issued = (int) $delivery->issued_count;
            if ($issued > 0) {
                $label .= ' / 累計'.$issued.'人';
            }

            return ['label' => $label, 'title' => $label];
        }

        $ids = [];
        foreach ((array) ($delivery->target_member_ids ?? []) as $rawId) {
            $id = (int) $rawId;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        if (CouponDeliveryType::isAllMembersDelivery((string) $delivery->delivery_type)) {
            $count = $ids !== [] ? count($ids) : (int) $delivery->issued_count;
            $label = $count > 0 ? '全員（'.number_format($count).'人）' : '全員';

            return ['label' => $label, 'title' => $label];
        }

        if ($ids === []) {
            $issued = (int) $delivery->issued_count;
            $label = $issued > 0 ? number_format($issued).'人' : '—';

            return ['label' => $label, 'title' => $label];
        }

        $names = [];
        foreach ($ids as $id) {
            $names[] = $memberLabels[$id] ?? ('ID:'.$id);
        }
        $full = implode('、', $names);
        $maxShow = 3;
        if (count($names) <= $maxShow) {
            return ['label' => $full, 'title' => $full];
        }

        $shown = array_slice($names, 0, $maxShow);
        $label = implode('、', $shown).' 他'.(count($names) - $maxShow).'名';

        return ['label' => $label, 'title' => $full];
    }

    private function formatTimingLabel(CouponDelivery $delivery): string
    {
        if ($delivery->status === CouponDeliveryStatus::ACTIVE) {
            return '有効（昇格時）';
        }
        if ($delivery->status === CouponDeliveryStatus::SCHEDULED && $delivery->scheduled_at !== null) {
            return '予約: '.$delivery->scheduled_at->format('Y/m/d H:i');
        }
        if ($delivery->sent_at !== null) {
            return '配信: '.$delivery->sent_at->format('Y/m/d H:i');
        }
        if ($delivery->created_at !== null) {
            return '作成: '.$delivery->created_at->format('Y/m/d H:i');
        }

        return '—';
    }

    private function statusBadgeClass(string $status): string
    {
        return match ($status) {
            CouponDeliveryStatus::SENT => 'success',
            CouponDeliveryStatus::SCHEDULED => 'info',
            CouponDeliveryStatus::ACTIVE => 'primary',
            CouponDeliveryStatus::FAILED => 'danger',
            CouponDeliveryStatus::CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    /** @return array<int, int> */
    private function countDeliveriesByCouponId(int $projectId): array
    {
        $counts = CouponDelivery::query()
            ->where('project_id', $projectId)
            ->selectRaw('coupon_id, COUNT(*) as delivery_count')
            ->groupBy('coupon_id')
            ->pluck('delivery_count', 'coupon_id');

        $result = [];
        foreach ($counts as $couponId => $count) {
            $result[(int) $couponId] = (int) $count;
        }

        return $result;
    }
}
