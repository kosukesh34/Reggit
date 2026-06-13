<?php

namespace App\Services\Admin;

use App\Enums\CouponDeliveryType;
use App\Models\Member;
use App\Models\Order;
use App\Enums\OrderStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * クーポン配布の対象会員を配信種別に応じて解決する。
 */
class CouponDeliveryTargetService
{
    public function __construct(
        private MemberAdminService $memberAdminService,
        private SalesSegmentService $salesSegmentService,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{member_ids: list<int>, segment_criteria: array<string, mixed>|null, error: string|null}
     */
    public function resolve(int $projectId, string $deliveryType, array $params): array
    {
        $memberIds = match ($deliveryType) {
            CouponDeliveryType::IMMEDIATE_ALL,
            CouponDeliveryType::SCHEDULED_ALL => [],

            CouponDeliveryType::IMMEDIATE_PERSONAL,
            CouponDeliveryType::SCHEDULED_PERSONAL => $this->resolvePersonalMemberIds($projectId, $params),

            CouponDeliveryType::IMMEDIATE_SEGMENT_SALES,
            CouponDeliveryType::SCHEDULED_SEGMENT_SALES => $this->resolveSalesSegmentMemberIds($projectId, $params),

            CouponDeliveryType::IMMEDIATE_BIRTHDAY,
            CouponDeliveryType::SCHEDULED_BIRTHDAY => $this->getBirthdayMonthMemberIds($projectId, $params)->all(),

            CouponDeliveryType::IMMEDIATE_NEW_MEMBERS,
            CouponDeliveryType::SCHEDULED_NEW_MEMBERS => $this->getNewMemberIds($projectId, $params)->all(),

            CouponDeliveryType::IMMEDIATE_INACTIVE,
            CouponDeliveryType::SCHEDULED_INACTIVE => $this->getInactiveMemberIds($projectId, $params)->all(),

            default => null,
        };

        if ($memberIds === null) {
            return ['member_ids' => [], 'segment_criteria' => null, 'error' => '配信種別が不正です'];
        }

        if (in_array($deliveryType, CouponDeliveryType::personalValues(), true)
            && $memberIds === []) {
            return ['member_ids' => [], 'segment_criteria' => null, 'error' => '配信対象の会員を1人以上選択してください'];
        }

        $segmentCriteria = $this->buildSegmentCriteria($deliveryType, $params);

        return ['member_ids' => $memberIds, 'segment_criteria' => $segmentCriteria, 'error' => null];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<int>
     */
    private function resolvePersonalMemberIds(int $projectId, array $params): array
    {
        $rawIds = collect($params['target_member_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($rawIds === []) {
            $input = trim((string) ($params['member_ids_input'] ?? ''));
            if ($input !== '') {
                $rawIds = array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', $input))));
            }
        }

        return $this->memberAdminService->filterValidMemberIds($projectId, $rawIds)->all();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<int>
     */
    private function resolveSalesSegmentMemberIds(int $projectId, array $params): array
    {
        $periodDays = (int) ($params['segment_period_days'] ?? 30);
        $condition = (string) ($params['segment_condition'] ?? 'min_amount');
        $isNoOrders = $condition === 'no_orders';
        $minAmount = isset($params['segment_min_amount']) ? (float) $params['segment_min_amount'] : null;

        return $this->salesSegmentService->getMemberIdsBySalesCriteria(
            $projectId,
            $periodDays,
            $isNoOrders ? null : $minAmount,
            $isNoOrders
        )->all();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, int>
     */
    public function getBirthdayMonthMemberIds(int $projectId, array $params): Collection
    {
        $month = (int) ($params['birthday_month'] ?? now()->month);
        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }

        return Member::query()
            ->where('project_id', $projectId)
            ->whereNotNull('birthday')
            ->whereMonth('birthday', $month)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, int>
     */
    public function getNewMemberIds(int $projectId, array $params): Collection
    {
        $days = max(1, (int) ($params['new_member_days'] ?? 30));
        $since = Carbon::now()->subDays($days)->startOfDay();

        return Member::query()
            ->where('project_id', $projectId)
            ->where('created_at', '>=', $since)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, int>
     */
    public function getInactiveMemberIds(int $projectId, array $params): Collection
    {
        $days = max(1, (int) ($params['inactive_days'] ?? 90));
        $since = Carbon::now()->subDays($days)->startOfDay();

        $userIdsWithRecentOrders = Order::query()
            ->where('project_id', $projectId)
            ->where('created_at', '>=', $since)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->distinct()
            ->pluck('user_id');

        $query = Member::query()->where('project_id', $projectId);

        if ($userIdsWithRecentOrders->isNotEmpty()) {
            $query->whereNotIn('user_id', $userIdsWithRecentOrders->all());
        }

        $query->where(function ($q) use ($since) {
            $q->whereNull('last_visited_at')
                ->orWhere('last_visited_at', '<', $since->toDateString());
        });

        return $query->pluck('id')->map(fn ($id) => (int) $id)->values();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    private function buildSegmentCriteria(string $deliveryType, array $params): ?array
    {
        if (in_array($deliveryType, CouponDeliveryType::segmentValues(), true)) {
            $condition = (string) ($params['segment_condition'] ?? 'min_amount');

            return [
                'period_days' => (int) ($params['segment_period_days'] ?? 30),
                'condition' => $condition,
                'min_total_amount' => $condition === 'min_amount' ? (float) ($params['segment_min_amount'] ?? 0) : null,
            ];
        }

        if (in_array($deliveryType, CouponDeliveryType::birthdayValues(), true)) {
            return ['birthday_month' => (int) ($params['birthday_month'] ?? now()->month)];
        }

        if (in_array($deliveryType, CouponDeliveryType::newMemberValues(), true)) {
            return ['new_member_days' => (int) ($params['new_member_days'] ?? 30)];
        }

        if (in_array($deliveryType, CouponDeliveryType::inactiveValues(), true)) {
            return ['inactive_days' => (int) ($params['inactive_days'] ?? 90)];
        }

        return null;
    }

    /**
     * プレビュー用: 対象会員数を返す。
     *
     * @param  array<string, mixed>  $params
     */
    public function countTargets(int $projectId, string $deliveryType, array $params): int
    {
        $resolved = $this->resolve($projectId, $deliveryType, $params);
        if ($resolved['error'] !== null) {
            return 0;
        }

        if (in_array($deliveryType, [CouponDeliveryType::IMMEDIATE_ALL, CouponDeliveryType::SCHEDULED_ALL], true)) {
            return Member::where('project_id', $projectId)->count();
        }

        return count($resolved['member_ids']);
    }
}
