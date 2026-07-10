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

    public function countAll(int $projectId): int;

    /**
     * @param  list<int>  $memberIds
     * @return list<int>
     */
    public function filterValidIds(int $projectId, array $memberIds): array;
}
