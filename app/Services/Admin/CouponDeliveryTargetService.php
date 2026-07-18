<?php

namespace App\Services\Admin;

use App\Domain\Coupon\Policies\CouponDeliverySegmentPolicy;
use App\Domain\Coupon\Repositories\CouponDeliveryTargetRepository;
use App\Enums\CouponDeliveryType;
use App\Models\CouponDelivery;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * クーポン配布の対象会員を配信種別に応じて解決する。
 */
class CouponDeliveryTargetService
{
    public function __construct(
        private CouponDeliveryTargetRepository $targetRepository,
        private CouponDeliverySegmentPolicy $segmentPolicy,
        private MemberSegmentService $memberSegmentService,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{member_ids: list<int>, segment_criteria: array<string, mixed>|null, error: string|null}
     */
    public function resolve(int $projectId, string $deliveryType, array $params): array
    {
        if ($this->segmentPolicy->isKnownDeliveryType($deliveryType) === false) {
            return ['member_ids' => [], 'segment_criteria' => null, 'error' => '配信種別が不正です'];
        }

        $memberIds = match ($deliveryType) {
            CouponDeliveryType::IMMEDIATE_ALL,
            CouponDeliveryType::SCHEDULED_ALL => [],

            CouponDeliveryType::IMMEDIATE_PERSONAL,
            CouponDeliveryType::SCHEDULED_PERSONAL => $this->resolvePersonalMemberIds($projectId, $params),

            CouponDeliveryType::IMMEDIATE_SEGMENT_SALES,
            CouponDeliveryType::SCHEDULED_SEGMENT_SALES => $this->resolveSalesSegmentMemberIds($projectId, $params),

            CouponDeliveryType::IMMEDIATE_SEGMENT_ATTRIBUTES,
            CouponDeliveryType::SCHEDULED_SEGMENT_ATTRIBUTES => $this->resolveAttributeSegmentMemberIds($projectId, $params),

            CouponDeliveryType::IMMEDIATE_BIRTHDAY,
            CouponDeliveryType::SCHEDULED_BIRTHDAY => $this->getBirthdayMonthMemberIds($projectId, $params)->all(),

            CouponDeliveryType::IMMEDIATE_NEW_MEMBERS,
            CouponDeliveryType::SCHEDULED_NEW_MEMBERS => $this->getNewMemberIds($projectId, $params)->all(),

            CouponDeliveryType::IMMEDIATE_INACTIVE,
            CouponDeliveryType::SCHEDULED_INACTIVE => $this->getInactiveMemberIds($projectId, $params)->all(),
        };

        if ($this->segmentPolicy->requiresPersonalMembers($deliveryType) && $memberIds === []) {
            return ['member_ids' => [], 'segment_criteria' => null, 'error' => '配信対象の会員を1人以上選択してください'];
        }

        if (in_array($deliveryType, CouponDeliveryType::attributeSegmentValues(), true) && $memberIds === []) {
            $attributeParams = $params;
            unset($attributeParams['segment_condition'], $attributeParams['segment_period_days'], $attributeParams['segment_min_amount']);
            $criteria = $this->memberSegmentService->buildCriteriaFromInput($attributeParams);
            if ($criteria === null) {
                return ['member_ids' => [], 'segment_criteria' => null, 'error' => '属性絞り込みでは1つ以上の条件を指定してください'];
            }
        }

        $segmentCriteria = $this->segmentPolicy->buildSegmentCriteria(
            $deliveryType,
            $params,
            (int) now()->month,
        );

        if (in_array($deliveryType, CouponDeliveryType::attributeSegmentValues(), true)) {
            $attributeParams = $params;
            unset($attributeParams['segment_condition'], $attributeParams['segment_period_days'], $attributeParams['segment_min_amount']);
            $segmentCriteria = $this->memberSegmentService->buildCriteriaFromInput($attributeParams);
        }

        return ['member_ids' => $memberIds, 'segment_criteria' => $segmentCriteria, 'error' => null];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<int>
     */
    private function resolveAttributeSegmentMemberIds(int $projectId, array $params): array
    {
        $attributeParams = $params;
        unset($attributeParams['segment_condition'], $attributeParams['segment_period_days'], $attributeParams['segment_min_amount']);

        $criteria = $this->memberSegmentService->buildCriteriaFromInput($attributeParams);
        if ($criteria === null) {
            return [];
        }

        return $this->memberSegmentService->getMemberIds($projectId, $criteria)->all();
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

        return $this->targetRepository->filterValidIds($projectId, $rawIds);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<int>
     */
    private function resolveSalesSegmentMemberIds(int $projectId, array $params): array
    {
        $periodDays = (int) ($params['segment_period_days'] ?? 30);
        $condition = (string) ($params['segment_condition'] ?? 'min_amount');
        $end = Carbon::today()->endOfDay();
        $start = Carbon::today()->subDays($periodDays)->startOfDay();

        if ($this->segmentPolicy->isNoOrdersSalesCondition($condition)) {
            return $this->targetRepository->findIdsWithNoOrdersInPeriod($projectId, $start, $end);
        }

        $minAmount = isset($params['segment_min_amount']) ? (float) $params['segment_min_amount'] : null;
        if ($minAmount === null || $minAmount <= 0) {
            return [];
        }

        return $this->targetRepository->findIdsByMinSales($projectId, $start, $end, $minAmount);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, int>
     */
    public function getBirthdayMonthMemberIds(int $projectId, array $params): Collection
    {
        $month = $this->segmentPolicy->normalizeBirthdayMonth(
            (int) ($params['birthday_month'] ?? now()->month),
            (int) now()->month,
        );

        return collect($this->targetRepository->findIdsByBirthdayMonth($projectId, $month));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, int>
     */
    public function getNewMemberIds(int $projectId, array $params): Collection
    {
        $days = $this->segmentPolicy->normalizePositiveDays((int) ($params['new_member_days'] ?? 30), 30);
        $since = Carbon::now()->subDays($days)->startOfDay();

        return collect($this->targetRepository->findIdsCreatedSince($projectId, $since));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, int>
     */
    public function getInactiveMemberIds(int $projectId, array $params): Collection
    {
        $days = $this->segmentPolicy->normalizePositiveDays((int) ($params['inactive_days'] ?? 90), 90);
        $since = Carbon::now()->subDays($days)->startOfDay();

        return collect($this->targetRepository->findInactiveIds($projectId, $since));
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

        if ($this->segmentPolicy->isAllMembersDelivery($deliveryType)) {
            return $this->targetRepository->countAll($projectId);
        }

        return count($resolved['member_ids']);
    }

    /**
     * 予約配信ジョブ等: 保存済み segment_criteria を resolve 用パラメータに変換する。
     *
     * @return array<string, mixed>
     */
    public function buildResolveParamsFromDelivery(CouponDelivery $delivery): array
    {
        $criteria = is_array($delivery->segment_criteria) ? $delivery->segment_criteria : [];
        $deliveryType = (string) $delivery->delivery_type;
        $params = [];

        if (in_array($deliveryType, CouponDeliveryType::segmentValues(), true)) {
            $condition = (string) ($criteria['condition'] ?? 'min_amount');
            $params['segment_period_days'] = (int) ($criteria['period_days'] ?? 30);
            $params['segment_condition'] = $condition;
            if ($condition === 'min_amount') {
                $params['segment_min_amount'] = (float) ($criteria['min_total_amount'] ?? 0);
            }
        }

        if (in_array($deliveryType, CouponDeliveryType::birthdayValues(), true)) {
            $params['birthday_month'] = (int) ($criteria['birthday_month'] ?? now()->month);
        }

        if (in_array($deliveryType, CouponDeliveryType::newMemberValues(), true)) {
            $params['new_member_days'] = (int) ($criteria['new_member_days'] ?? 30);
        }

        if (in_array($deliveryType, CouponDeliveryType::inactiveValues(), true)) {
            $params['inactive_days'] = (int) ($criteria['inactive_days'] ?? 90);
        }

        if (in_array($deliveryType, CouponDeliveryType::attributeSegmentValues(), true)) {
            foreach ([
                'channel' => 'segment_channel',
                'birthday_month' => 'segment_birthday_month',
                'visit_count_min' => 'segment_visit_count_min',
                'visit_count_max' => 'segment_visit_count_max',
                'lifetime_points_min' => 'segment_lifetime_points_min',
                'lifetime_points_max' => 'segment_lifetime_points_max',
                'last_visit_mode' => 'segment_last_visit_mode',
                'last_visit_days' => 'segment_last_visit_days',
                'sex' => 'segment_sex',
                'rank' => 'segment_rank',
                'period_days' => 'segment_period_days',
                'min_total_amount' => 'segment_min_amount',
            ] as $from => $to) {
                if (array_key_exists($from, $criteria)) {
                    $params[$to] = $criteria[$from];
                }
            }
            if (isset($criteria['no_orders']) && $criteria['no_orders'] === true) {
                $params['segment_condition'] = 'no_orders';
            } elseif (array_key_exists('min_total_amount', $criteria)) {
                $params['segment_condition'] = 'min_amount';
            }
        }

        $params['target_member_ids'] = is_array($delivery->target_member_ids) ? $delivery->target_member_ids : [];

        return $params;
    }
}
