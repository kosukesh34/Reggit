<?php

namespace App\Services\Admin;

use App\Domain\Coupon\Repositories\CouponDeliveryTargetRepository;
use App\Enums\RfmSegment;
use App\Support\MemberChannel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * 会員属性によるセグメント絞り込み（AND）。お知らせ・クーポン配布で共有する。
 */
class MemberSegmentService
{
    public const LAST_VISIT_INACTIVE = 'inactive_days';

    public const LAST_VISIT_WITHIN = 'visited_within_days';

    public const LAST_VISIT_DAYS_AFTER = 'days_after';

    public function __construct(
        private CouponDeliveryTargetRepository $targetRepository,
        private RfmAnalysisService $rfmAnalysisService,
    ) {}

    /**
     * リクエスト/フォーム値から segment_criteria を組み立てる。有効な条件がなければ null。
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>|null
     */
    public function buildCriteriaFromInput(array $input): ?array
    {
        $criteria = [];

        $channel = trim((string) ($input['segment_channel'] ?? $input['channel'] ?? ''));
        if (in_array($channel, [MemberChannel::LINE, MemberChannel::APP, MemberChannel::BOTH], true)) {
            $criteria['channel'] = $channel;
        }

        $birthdayMonths = $this->normalizeIntList(
            $input['segment_birthday_months'] ?? $input['birthday_months'] ?? [],
            1,
            12,
        );
        if ($birthdayMonths !== []) {
            $criteria['birthday_months'] = $birthdayMonths;
        } else {
            $birthdayMonth = $input['segment_birthday_month'] ?? $input['birthday_month'] ?? null;
            if ($birthdayMonth !== null && $birthdayMonth !== '') {
                $month = (int) $birthdayMonth;
                if ($month >= 1 && $month <= 12) {
                    $criteria['birthday_month'] = $month;
                }
            }
        }

        $tagIds = $this->normalizeIntList($input['segment_tag_ids'] ?? $input['tag_ids'] ?? []);
        if ($tagIds !== []) {
            $criteria['tag_ids'] = $tagIds;
        }

        $visitMin = $input['segment_visit_count_min'] ?? $input['visit_count_min'] ?? null;
        $visitMax = $input['segment_visit_count_max'] ?? $input['visit_count_max'] ?? null;
        if ($visitMin !== null && $visitMin !== '') {
            $criteria['visit_count_min'] = max(0, (int) $visitMin);
        }
        if ($visitMax !== null && $visitMax !== '') {
            $criteria['visit_count_max'] = max(0, (int) $visitMax);
        }

        $pointsMin = $input['segment_lifetime_points_min'] ?? $input['lifetime_points_min'] ?? null;
        $pointsMax = $input['segment_lifetime_points_max'] ?? $input['lifetime_points_max'] ?? null;
        if ($pointsMin !== null && $pointsMin !== '') {
            $criteria['lifetime_points_min'] = max(0, (int) $pointsMin);
        }
        if ($pointsMax !== null && $pointsMax !== '') {
            $criteria['lifetime_points_max'] = max(0, (int) $pointsMax);
        }

        $lastVisitMode = trim((string) ($input['segment_last_visit_mode'] ?? $input['last_visit_mode'] ?? ''));
        $lastVisitDays = $input['segment_last_visit_days'] ?? $input['last_visit_days'] ?? null;
        if (in_array($lastVisitMode, [self::LAST_VISIT_INACTIVE, self::LAST_VISIT_WITHIN, self::LAST_VISIT_DAYS_AFTER], true)
            && $lastVisitDays !== null && $lastVisitDays !== '') {
            $criteria['last_visit_mode'] = $lastVisitMode;
            $criteria['last_visit_days'] = max(1, (int) $lastVisitDays);
        }

        $sex = trim((string) ($input['segment_sex'] ?? $input['sex'] ?? ''));
        if ($sex === '1' || $sex === '2') {
            $criteria['sex'] = $sex;
        }

        $rank = trim((string) ($input['segment_rank'] ?? $input['rank'] ?? $input['target_rank'] ?? ''));
        if ($rank !== '') {
            $criteria['rank'] = $rank;
        }

        $rfmSegments = $this->normalizeRfmSegments($input['segment_rfm_segments'] ?? $input['rfm_segments'] ?? []);
        if ($rfmSegments !== []) {
            $criteria['rfm_segments'] = $rfmSegments;
        }

        $productId = $input['segment_product_id'] ?? $input['product_id'] ?? null;
        if ($productId !== null && $productId !== '') {
            $parsedProductId = (int) $productId;
            if ($parsedProductId > 0) {
                $criteria['product_id'] = $parsedProductId;
            }
        }

        $stampCardId = $input['segment_stamp_card_id'] ?? $input['stamp_card_id'] ?? null;
        $stampCountMin = $input['segment_stamp_count_min'] ?? $input['stamp_count_min'] ?? null;
        $stampCompleted = $input['segment_stamp_completed'] ?? $input['stamp_completed'] ?? null;
        $isStampCompleted = $stampCompleted === true || $stampCompleted === 1 || $stampCompleted === '1' || $stampCompleted === 'on';
        if ($stampCardId !== null && $stampCardId !== '') {
            $parsedStampCardId = (int) $stampCardId;
            if ($parsedStampCardId > 0) {
                $criteria['stamp_card_id'] = $parsedStampCardId;
            }
        }
        if ($stampCountMin !== null && $stampCountMin !== '') {
            $criteria['stamp_count_min'] = max(0, (int) $stampCountMin);
        }
        if ($isStampCompleted) {
            $criteria['stamp_completed'] = true;
        }

        $salesCondition = trim((string) ($input['segment_condition'] ?? ''));
        $periodDays = $input['segment_period_days'] ?? null;
        if ($salesCondition === 'no_orders' || $salesCondition === 'min_amount') {
            $criteria['period_days'] = max(1, min(365, (int) ($periodDays ?? 30)));
            $criteria['no_orders'] = $salesCondition === 'no_orders';
            if ($salesCondition === 'min_amount') {
                $minAmount = isset($input['segment_min_amount']) ? (float) $input['segment_min_amount'] : null;
                if ($minAmount !== null && $minAmount >= 0) {
                    $criteria['min_total_amount'] = $minAmount;
                }
            }
        }

        return $criteria === [] ? null : $criteria;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return Collection<int, int>
     */
    public function getMemberIds(int $projectId, array $criteria): Collection
    {
        $sets = [];

        if (isset($criteria['channel']) && is_string($criteria['channel'])) {
            $sets[] = $this->targetRepository->findIdsByChannel($projectId, $criteria['channel']);
        }

        if (isset($criteria['birthday_month'])) {
            $sets[] = $this->targetRepository->findIdsByBirthdayMonth($projectId, (int) $criteria['birthday_month']);
        }

        if (isset($criteria['birthday_months']) && is_array($criteria['birthday_months'])) {
            $birthdayIds = collect($criteria['birthday_months'])
                ->flatMap(fn ($month) => $this->targetRepository->findIdsByBirthdayMonth($projectId, (int) $month))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
            $sets[] = $birthdayIds;
        }

        if (isset($criteria['tag_ids']) && is_array($criteria['tag_ids'])) {
            $sets[] = $this->targetRepository->findIdsByTagIds($projectId, $criteria['tag_ids']);
        }

        $hasVisitMin = array_key_exists('visit_count_min', $criteria);
        $hasVisitMax = array_key_exists('visit_count_max', $criteria);
        if ($hasVisitMin || $hasVisitMax) {
            $sets[] = $this->targetRepository->findIdsByVisitCount(
                $projectId,
                $hasVisitMin ? (int) $criteria['visit_count_min'] : null,
                $hasVisitMax ? (int) $criteria['visit_count_max'] : null,
            );
        }

        $hasPointsMin = array_key_exists('lifetime_points_min', $criteria);
        $hasPointsMax = array_key_exists('lifetime_points_max', $criteria);
        if ($hasPointsMin || $hasPointsMax) {
            $sets[] = $this->targetRepository->findIdsByLifetimeGrantedPoints(
                $projectId,
                $hasPointsMin ? (int) $criteria['lifetime_points_min'] : null,
                $hasPointsMax ? (int) $criteria['lifetime_points_max'] : null,
            );
        }

        if (isset($criteria['last_visit_mode'], $criteria['last_visit_days'])) {
            $sets[] = $this->targetRepository->findIdsByLastVisited(
                $projectId,
                (string) $criteria['last_visit_mode'],
                (int) $criteria['last_visit_days'],
            );
        }

        if (isset($criteria['sex']) && is_string($criteria['sex'])) {
            $sets[] = $this->targetRepository->findIdsBySex($projectId, $criteria['sex']);
        }

        if (isset($criteria['rank']) && is_string($criteria['rank'])) {
            $sets[] = $this->targetRepository->findIdsByRank($projectId, $criteria['rank']);
        }

        if (isset($criteria['rfm_segments']) && is_array($criteria['rfm_segments'])) {
            $sets[] = $this->rfmAnalysisService->findMemberIdsBySegments($projectId, $criteria['rfm_segments']);
        }

        if (isset($criteria['product_id'])) {
            $sets[] = $this->targetRepository->findIdsByPurchasedProduct($projectId, (int) $criteria['product_id']);
        }

        $hasStampFilter = isset($criteria['stamp_card_id'])
            || isset($criteria['stamp_count_min'])
            || (($criteria['stamp_completed'] ?? false) === true);
        if ($hasStampFilter) {
            $sets[] = $this->targetRepository->findIdsByStampProgress(
                $projectId,
                isset($criteria['stamp_card_id']) ? (int) $criteria['stamp_card_id'] : null,
                isset($criteria['stamp_count_min']) ? (int) $criteria['stamp_count_min'] : null,
                ($criteria['stamp_completed'] ?? false) === true,
            );
        }

        $periodDays = isset($criteria['period_days']) ? (int) $criteria['period_days'] : 0;
        $noOrders = (bool) ($criteria['no_orders'] ?? false);
        $minAmount = isset($criteria['min_total_amount']) ? (float) $criteria['min_total_amount'] : null;
        if ($periodDays > 0 && ($noOrders || ($minAmount !== null && $minAmount >= 0))) {
            $end = Carbon::today()->endOfDay();
            $start = Carbon::today()->subDays($periodDays)->startOfDay();
            if ($noOrders) {
                $sets[] = $this->targetRepository->findIdsWithNoOrdersInPeriod($projectId, $start, $end);
            } elseif ($minAmount !== null) {
                $sets[] = $this->targetRepository->findIdsByMinSales($projectId, $start, $end, $minAmount);
            }
        }

        if ($sets === []) {
            return collect();
        }

        $intersected = array_shift($sets);
        foreach ($sets as $set) {
            $intersected = array_values(array_intersect($intersected, $set));
        }

        return collect($intersected)->map(fn ($id) => (int) $id)->values();
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function countMembers(int $projectId, array $criteria): int
    {
        return $this->getMemberIds($projectId, $criteria)->count();
    }

    /**
     * @return list<string>
     */
    private function normalizeRfmSegments(mixed $values): array
    {
        $items = is_array($values) ? $values : [$values];

        return collect($items)
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => in_array($value, RfmSegment::keys(), true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function normalizeIntList(mixed $values, int $min = 1, ?int $max = null): array
    {
        $items = is_array($values) ? $values : [$values];

        return collect($items)
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value): bool => $value >= $min && ($max === null || $value <= $max))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
