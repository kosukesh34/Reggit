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
            CouponDeliveryType::SCHEDULED_ALL => $this->targetRepository->findAllIds($projectId),

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

            CouponDeliveryType::IMMEDIATE_RANK,
            CouponDeliveryType::SCHEDULED_RANK => $this->getRankMemberIds($projectId, $params)->all(),

            CouponDeliveryType::IMMEDIATE_STAMP,
            CouponDeliveryType::SCHEDULED_STAMP => $this->getStampMemberIds($projectId, $params)->all(),

            CouponDeliveryType::IMMEDIATE_DAYS_AFTER_VISIT,
            CouponDeliveryType::SCHEDULED_DAYS_AFTER_VISIT => $this->getDaysAfterVisitMemberIds($projectId, $params)->all(),

            CouponDeliveryType::EVENT_RANK_UP_ARRIVAL,
            CouponDeliveryType::EVENT_WEATHER_RAIN => [],
        };

        if ($this->segmentPolicy->requiresPersonalMembers($deliveryType) && $memberIds === []) {
            return ['member_ids' => [], 'segment_criteria' => null, 'error' => '配信対象の会員を1人以上選択してください'];
        }

        $isAttributeSegment = in_array($deliveryType, CouponDeliveryType::attributeSegmentValues(), true);
        $segmentCriteria = $isAttributeSegment
            ? $this->attributeCriteria($params)
            : $this->segmentPolicy->buildSegmentCriteria($deliveryType, $params, (int) now()->month);

        if ($isAttributeSegment && $segmentCriteria === null) {
            return ['member_ids' => [], 'segment_criteria' => null, 'error' => '属性絞り込みでは1つ以上の条件を指定してください'];
        }

        if (CouponDeliveryType::isEventRankUpArrival($deliveryType)) {
            $rank = trim((string) ($segmentCriteria['rank'] ?? ''));
            if ($rank === '') {
                return ['member_ids' => [], 'segment_criteria' => null, 'error' => '到達ランクを選択してください'];
            }
        }

        return ['member_ids' => $memberIds, 'segment_criteria' => $segmentCriteria, 'error' => null];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<int>
     */
    private function resolveAttributeSegmentMemberIds(int $projectId, array $params): array
    {
        $criteria = $this->attributeCriteria($params);
        if ($criteria === null) {
            return [];
        }

        return $this->memberSegmentService->getMemberIds($projectId, $criteria)->all();
    }

    /**
     * 予約配信では保存済みの segment_criteria、フォームからは入力値を使う。
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    private function attributeCriteria(array $params): ?array
    {
        return is_array($params['segment_criteria'] ?? null)
            ? $params['segment_criteria']
            : $this->memberSegmentService->buildCriteriaFromInput($params);
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
     * @param  array<string, mixed>  $params
     * @return Collection<int, int>
     */
    public function getRankMemberIds(int $projectId, array $params): Collection
    {
        $rank = trim((string) ($params['target_rank'] ?? $params['segment_rank'] ?? ''));
        if ($rank === '') {
            return collect();
        }

        return collect($this->targetRepository->findIdsByRank($projectId, $rank));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, int>
     */
    public function getStampMemberIds(int $projectId, array $params): Collection
    {
        $stampCardId = isset($params['stamp_card_id']) && $params['stamp_card_id'] !== ''
            ? (int) $params['stamp_card_id']
            : (isset($params['segment_stamp_card_id']) && $params['segment_stamp_card_id'] !== ''
                ? (int) $params['segment_stamp_card_id']
                : null);
        $minStamps = isset($params['stamp_count_min']) && $params['stamp_count_min'] !== ''
            ? (int) $params['stamp_count_min']
            : (isset($params['segment_stamp_count_min']) && $params['segment_stamp_count_min'] !== ''
                ? (int) $params['segment_stamp_count_min']
                : null);
        $completedRaw = $params['stamp_completed'] ?? $params['segment_stamp_completed'] ?? false;
        $completedOnly = $completedRaw === true || $completedRaw === 1 || $completedRaw === '1' || $completedRaw === 'on';

        if ($stampCardId === null && $minStamps === null && $completedOnly === false) {
            return collect();
        }

        return collect($this->targetRepository->findIdsByStampProgress(
            $projectId,
            $stampCardId,
            $minStamps,
            $completedOnly,
        ));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, int>
     */
    public function getDaysAfterVisitMemberIds(int $projectId, array $params): Collection
    {
        $days = $this->segmentPolicy->normalizePositiveDays(
            (int) ($params['days_after_last_visit'] ?? $params['segment_last_visit_days'] ?? 7),
            7,
        );

        return collect($this->targetRepository->findIdsByLastVisited(
            $projectId,
            MemberSegmentService::LAST_VISIT_DAYS_AFTER,
            $days,
        ));
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

        if (CouponDeliveryType::isEventRankUpArrival($deliveryType)) {
            return -1;
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

        if (in_array($deliveryType, CouponDeliveryType::rankValues(), true)) {
            $params['target_rank'] = (string) ($criteria['rank'] ?? '');
            $params['segment_rank'] = $params['target_rank'];
        }

        if (in_array($deliveryType, CouponDeliveryType::stampValues(), true)) {
            if (isset($criteria['stamp_card_id'])) {
                $params['stamp_card_id'] = (int) $criteria['stamp_card_id'];
            }
            if (isset($criteria['stamp_count_min'])) {
                $params['stamp_count_min'] = (int) $criteria['stamp_count_min'];
            }
            if (($criteria['stamp_completed'] ?? false) === true) {
                $params['stamp_completed'] = '1';
            }
        }

        if (in_array($deliveryType, CouponDeliveryType::daysAfterVisitValues(), true)) {
            $params['days_after_last_visit'] = (int) ($criteria['days_after_last_visit'] ?? 7);
        }

        if (in_array($deliveryType, CouponDeliveryType::attributeSegmentValues(), true) && $criteria !== []) {
            $params['segment_criteria'] = $criteria;
        }

        $params['target_member_ids'] = is_array($delivery->target_member_ids) ? $delivery->target_member_ids : [];

        return $params;
    }
}
