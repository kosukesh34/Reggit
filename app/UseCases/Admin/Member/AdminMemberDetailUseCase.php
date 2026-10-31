<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminQueryService;
use App\Services\Admin\MemberHistoryQueryService;
use App\UseCases\Admin\Member\Data\AdminMemberDetailOutput;

class AdminMemberDetailUseCase
{
    public function __construct(
        private MemberAdminQueryService $memberAdminService,
        private MemberHistoryQueryService $historyQueryService,
    ) {}

    /**
     * @param  array<string, string|null>  $filters
     */
    public function handle(int $projectId, int $memberId, array $filters = []): AdminMemberDetailOutput
    {
        $member = $this->memberAdminService->findOrFail($memberId, $projectId);
        $purchaseSummary = $this->memberAdminService->getPurchaseSummary($member);

        $historyFilters = $this->normalizeHistoryFilters($filters);

        return new AdminMemberDetailOutput(
            $member,
            $purchaseSummary,
            $this->historyQueryService->getPaginatedVisits(
                $memberId,
                $historyFilters['visits_q'],
                MemberHistoryQueryService::HISTORY_PER_PAGE,
                'visits_page',
                $historyFilters['visits_from'],
                $historyFilters['visits_to'],
            ),
            $this->historyQueryService->getPaginatedPointTransactions(
                $memberId,
                $historyFilters['points_q'],
                MemberHistoryQueryService::HISTORY_PER_PAGE,
                'points_page',
                $historyFilters['points_from'],
                $historyFilters['points_to'],
            ),
            $this->historyQueryService->getPaginatedMemberCoupons(
                $memberId,
                $historyFilters['coupons_q'],
                MemberHistoryQueryService::HISTORY_PER_PAGE,
                'coupons_page',
                $historyFilters['coupons_from'],
                $historyFilters['coupons_to'],
            ),
            $this->historyQueryService->getPaginatedStampTransactions(
                $memberId,
                $historyFilters['stamps_q'],
                MemberHistoryQueryService::HISTORY_PER_PAGE,
                'stamps_page',
                $historyFilters['stamps_from'],
                $historyFilters['stamps_to'],
            ),
            $this->historyQueryService->getPaginatedRankHistories(
                $memberId,
                $historyFilters['rank_q'],
                MemberHistoryQueryService::HISTORY_PER_PAGE,
                'rank_page',
                $historyFilters['rank_from'],
                $historyFilters['rank_to'],
            ),
            $this->historyQueryService->getPaginatedCouponRedemptions(
                $memberId,
                $historyFilters['redemptions_q'],
                MemberHistoryQueryService::HISTORY_PER_PAGE,
                'redemptions_page',
                $historyFilters['redemptions_from'],
                $historyFilters['redemptions_to'],
            ),
            $historyFilters,
        );
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return array{
     *     visits_q: string, visits_from: string, visits_to: string,
     *     points_q: string, points_from: string, points_to: string,
     *     coupons_q: string, coupons_from: string, coupons_to: string,
     *     stamps_q: string, stamps_from: string, stamps_to: string,
     *     rank_q: string, rank_from: string, rank_to: string,
     *     redemptions_q: string, redemptions_from: string, redemptions_to: string
     * }
     */
    private function normalizeHistoryFilters(array $filters): array
    {
        $keys = [
            'visits_q', 'visits_from', 'visits_to',
            'points_q', 'points_from', 'points_to',
            'coupons_q', 'coupons_from', 'coupons_to',
            'stamps_q', 'stamps_from', 'stamps_to',
            'rank_q', 'rank_from', 'rank_to',
            'redemptions_q', 'redemptions_from', 'redemptions_to',
        ];
        $normalized = [];
        foreach ($keys as $key) {
            $normalized[$key] = trim((string) ($filters[$key] ?? ''));
        }

        return $normalized;
    }
}
