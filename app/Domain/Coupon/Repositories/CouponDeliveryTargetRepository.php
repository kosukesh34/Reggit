<?php

namespace App\Domain\Coupon\Repositories;

/**
 * クーポン配信の対象会員ID解決（クエリ抽象）。
 */
interface CouponDeliveryTargetRepository
{
    /** @return list<int> */
    public function findIdsByBirthdayMonth(int $projectId, int $month): array;

    /** @return list<int> */
    public function findIdsCreatedSince(int $projectId, \DateTimeInterface $since): array;

    /** @return list<int> */
    public function findInactiveIds(int $projectId, \DateTimeInterface $since): array;

    /** @return list<int> */
    public function findIdsByMinSales(
        int $projectId,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        float $minTotalAmount,
    ): array;

    /** @return list<int> */
    public function findIdsWithNoOrdersInPeriod(
        int $projectId,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
    ): array;

    /**
     * @param  string  $channel  line|app|both
     * @return list<int>
     */
    public function findIdsByChannel(int $projectId, string $channel): array;

    /**
     * 来店回数（キャンセル以外の注文件数）で絞り込む。
     *
     * @return list<int>
     */
    public function findIdsByVisitCount(int $projectId, ?int $min, ?int $max): array;

    /**
     * 最終来店日で絞り込む。
     *
     * @param  string  $mode  inactive_days|visited_within_days
     * @return list<int>
     */
    public function findIdsByLastVisited(int $projectId, string $mode, int $days): array;

    /** @return list<int> */
    public function findIdsBySex(int $projectId, string $sex): array;

    /** @return list<int> */
    public function findIdsByRank(int $projectId, string $rank): array;

    /**
     * 累計付与ポイントで絞り込む。
     *
     * @return list<int>
     */
    public function findIdsByLifetimeGrantedPoints(int $projectId, ?int $min, ?int $max): array;

    public function countAll(int $projectId): int;

    /**
     * @param  list<int>  $memberIds
     * @return list<int>
     */
    public function filterValidIds(int $projectId, array $memberIds): array;
}
