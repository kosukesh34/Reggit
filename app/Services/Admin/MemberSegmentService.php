<?php

namespace App\Services\Admin;

use App\Domain\Coupon\Repositories\CouponDeliveryTargetRepository;
use App\Enums\RfmSegment;
use App\Enums\SegmentCombineMode;
use App\Models\MemberAudienceGroup;
use App\Support\MemberChannel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * 会員属性によるセグメント絞り込み。お知らせ・クーポン配布で共有する。
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

        $matchMode = $input['segment_match_mode'] ?? null;
        if (in_array($matchMode, SegmentCombineMode::keys(), true)) {
            $criteria['match_mode'] = $matchMode;
        }

        $operators = collect((array) ($input['segment_operators'] ?? []))
            ->mapWithKeys(fn ($value, $key): array => [(string) $key => $value === 'or' ? 'or' : 'and'])
            ->all();
        if ($operators !== []) {
            $criteria['operators'] = $operators;
        }

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
            if ($this->listMode($input, 'tags') === SegmentCombineMode::ALL) {
                $criteria['tag_ids_mode'] = SegmentCombineMode::ALL;
            }
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

        $profileFilters = [];
        foreach ((array) ($input['segment_profile'] ?? []) as $key => $value) {
            if (preg_match('/^[a-z0-9_]+$/', (string) $key) !== 1) {
                continue;
            }
            $values = collect(is_array($value) ? $value : [$value])
                ->map(fn ($item): string => trim((string) $item))
                ->filter(fn (string $item): bool => $item !== '')
                ->unique()
                ->values()
                ->all();
            if ($values !== []) {
                $profileFilters[(string) $key] = $values;
                if ($this->profileListMode($input, (string) $key) === SegmentCombineMode::ALL) {
                    $criteria['profile_modes'][(string) $key] = SegmentCombineMode::ALL;
                }
            }
        }
        if ($profileFilters !== []) {
            $criteria['profile'] = $profileFilters;
        }

        $groupIds = $this->normalizeIntList($input['segment_audience_group_ids'] ?? []);
        if ($groupIds !== []) {
            $criteria['audience_group_ids'] = $groupIds;
        }

        return array_diff_key($criteria, ['operators' => true, 'match_mode' => true]) === [] ? null : $criteria;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return Collection<int, int>
     */
    public function getMemberIds(int $projectId, array $criteria): Collection
    {
        /** @var list<array{key: string, ids: list<int>}> $sets */
        $sets = [];
        $add = static function (string $key, array $ids) use (&$sets): void {
            $sets[] = ['key' => $key, 'ids' => $ids];
        };

        if (isset($criteria['channel']) && is_string($criteria['channel'])) {
            $add('channel', $this->targetRepository->findIdsByChannel($projectId, $criteria['channel']));
        }

        if (isset($criteria['birthday_month'])) {
            $add('birthday', $this->targetRepository->findIdsByBirthdayMonth($projectId, (int) $criteria['birthday_month']));
        }

        if (isset($criteria['birthday_months']) && is_array($criteria['birthday_months'])) {
            $birthdaySets = collect($criteria['birthday_months'])
                ->map(fn ($month) => $this->targetRepository->findIdsByBirthdayMonth($projectId, (int) $month))
                ->all();
            $add('birthday', $this->combineIdSets($birthdaySets, SegmentCombineMode::ANY));
        }

        if (isset($criteria['tag_ids']) && is_array($criteria['tag_ids'])) {
            if (SegmentCombineMode::fromInput($criteria['tag_ids_mode'] ?? null) === SegmentCombineMode::ALL) {
                $tagSets = collect($criteria['tag_ids'])
                    ->map(fn ($tagId) => $this->targetRepository->findIdsByTagIds($projectId, [(int) $tagId]))
                    ->all();
                $add('tags', $this->combineIdSets($tagSets, SegmentCombineMode::ALL));
            } else {
                $add('tags', $this->targetRepository->findIdsByTagIds($projectId, $criteria['tag_ids']));
            }
        }

        $hasVisitMin = array_key_exists('visit_count_min', $criteria);
        $hasVisitMax = array_key_exists('visit_count_max', $criteria);
        if ($hasVisitMin || $hasVisitMax) {
            $add('visits', $this->targetRepository->findIdsByVisitCount(
                $projectId,
                $hasVisitMin ? (int) $criteria['visit_count_min'] : null,
                $hasVisitMax ? (int) $criteria['visit_count_max'] : null,
            ));
        }

        $hasPointsMin = array_key_exists('lifetime_points_min', $criteria);
        $hasPointsMax = array_key_exists('lifetime_points_max', $criteria);
        if ($hasPointsMin || $hasPointsMax) {
            $add('points', $this->targetRepository->findIdsByLifetimeGrantedPoints(
                $projectId,
                $hasPointsMin ? (int) $criteria['lifetime_points_min'] : null,
                $hasPointsMax ? (int) $criteria['lifetime_points_max'] : null,
            ));
        }

        if (isset($criteria['last_visit_mode'], $criteria['last_visit_days'])) {
            $add('last_visit', $this->targetRepository->findIdsByLastVisited(
                $projectId,
                (string) $criteria['last_visit_mode'],
                (int) $criteria['last_visit_days'],
            ));
        }

        if (isset($criteria['sex']) && is_string($criteria['sex'])) {
            $add('sex', $this->targetRepository->findIdsBySex($projectId, $criteria['sex']));
        }

        if (isset($criteria['rank']) && is_string($criteria['rank'])) {
            $add('rank', $this->targetRepository->findIdsByRank($projectId, $criteria['rank']));
        }

        if (isset($criteria['rfm_segments']) && is_array($criteria['rfm_segments'])) {
            $add('rfm', $this->rfmAnalysisService->findMemberIdsBySegments($projectId, $criteria['rfm_segments']));
        }

        if (isset($criteria['product_id'])) {
            $add('product', $this->targetRepository->findIdsByPurchasedProduct($projectId, (int) $criteria['product_id']));
        }

        $hasStampFilter = isset($criteria['stamp_card_id'])
            || isset($criteria['stamp_count_min'])
            || (($criteria['stamp_completed'] ?? false) === true);
        if ($hasStampFilter) {
            $add('stamp', $this->targetRepository->findIdsByStampProgress(
                $projectId,
                isset($criteria['stamp_card_id']) ? (int) $criteria['stamp_card_id'] : null,
                isset($criteria['stamp_count_min']) ? (int) $criteria['stamp_count_min'] : null,
                ($criteria['stamp_completed'] ?? false) === true,
            ));
        }

        $periodDays = isset($criteria['period_days']) ? (int) $criteria['period_days'] : 0;
        $noOrders = (bool) ($criteria['no_orders'] ?? false);
        $minAmount = isset($criteria['min_total_amount']) ? (float) $criteria['min_total_amount'] : null;
        if ($periodDays > 0 && ($noOrders || ($minAmount !== null && $minAmount >= 0))) {
            $end = Carbon::today()->endOfDay();
            $start = Carbon::today()->subDays($periodDays)->startOfDay();
            if ($noOrders) {
                $add('sales', $this->targetRepository->findIdsWithNoOrdersInPeriod($projectId, $start, $end));
            } elseif ($minAmount !== null) {
                $add('sales', $this->targetRepository->findIdsByMinSales($projectId, $start, $end, $minAmount));
            }
        }

        $profileModes = is_array($criteria['profile_modes'] ?? null) ? $criteria['profile_modes'] : [];
        foreach ((array) ($criteria['profile'] ?? []) as $key => $values) {
            if (is_array($values)) {
                $add('profile_'.$key, $this->targetRepository->findIdsByProfileField(
                    $projectId,
                    (string) $key,
                    $values,
                    SegmentCombineMode::fromInput($profileModes[$key] ?? null) === SegmentCombineMode::ALL,
                ));
            }
        }

        if (isset($criteria['audience_group_ids']) && is_array($criteria['audience_group_ids'])) {
            $groupIds = collect($this->memberIdsForAudienceGroups($projectId, $criteria['audience_group_ids']));
            $add('groups', $groupIds->all());
        }

        if ($sets === []) {
            return collect();
        }

        $displayOrder = ['groups', 'channel', 'birthday', 'sex', 'rfm', 'tags', 'visits', 'rank', 'points', 'last_visit', 'product', 'stamp', 'sales'];
        usort($sets, static function (array $left, array $right) use ($displayOrder): int {
            $leftPosition = array_search($left['key'], $displayOrder, true);
            $rightPosition = array_search($right['key'], $displayOrder, true);
            $leftPosition = $leftPosition === false ? count($displayOrder) : $leftPosition;
            $rightPosition = $rightPosition === false ? count($displayOrder) : $rightPosition;

            return $leftPosition <=> $rightPosition;
        });

        $first = array_shift($sets);
        $resolved = $first['ids'];
        $operators = is_array($criteria['operators'] ?? null) ? $criteria['operators'] : [];
        foreach ($sets as $set) {
            $operator = match ($criteria['match_mode'] ?? null) {
                SegmentCombineMode::ANY => 'or',
                SegmentCombineMode::ALL => 'and',
                default => $operators[$set['key']] ?? 'and',
            };
            $resolved = $operator === 'or'
                ? array_values(array_unique(array_merge($resolved, $set['ids'])))
                : array_values(array_intersect($resolved, $set['ids']));
        }

        return collect($resolved)->map(fn ($id) => (int) $id)->values();
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function countMembers(int $projectId, array $criteria): int
    {
        return $this->getMemberIds($projectId, $criteria)->count();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function listMode(array $input, string $key): string
    {
        $modes = $input['segment_value_mode'] ?? [];

        return SegmentCombineMode::fromInput(is_array($modes) ? ($modes[$key] ?? null) : null);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function profileListMode(array $input, string $key): string
    {
        $modes = $input['segment_value_mode'] ?? [];
        $profileModes = is_array($modes) && is_array($modes['profile'] ?? null) ? $modes['profile'] : [];

        return SegmentCombineMode::fromInput($profileModes[$key] ?? null);
    }

    /**
     * @param  list<list<int>>  $sets
     * @return list<int>
     */
    private function combineIdSets(array $sets, string $mode): array
    {
        if ($sets === []) {
            return [];
        }
        $resolved = array_map('intval', array_shift($sets));
        foreach ($sets as $set) {
            $ids = array_map('intval', $set);
            $resolved = $mode === SegmentCombineMode::ALL
                ? array_values(array_intersect($resolved, $ids))
                : array_values(array_unique(array_merge($resolved, $ids)));
        }

        return $resolved;
    }

    /**
     * @param  list<int|string>  $groupIds
     * @return list<int>
     */
    private function memberIdsForAudienceGroups(int $projectId, array $groupIds): array
    {
        $ids = [];
        $groups = MemberAudienceGroup::query()
            ->where('project_id', $projectId)
            ->whereIn('id', $groupIds)
            ->get();
        foreach ($groups as $group) {
            $criteria = is_array($group->criteria) ? $group->criteria : [];
            unset($criteria['audience_group_ids']);
            if ($criteria === []) {
                continue;
            }
            foreach ($this->getMemberIds($projectId, $criteria) as $memberId) {
                $ids[] = (int) $memberId;
            }
        }

        return array_values(array_unique($ids));
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
