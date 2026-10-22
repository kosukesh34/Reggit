<?php

namespace App\Domain\Coupon\Repositories;

/**
 * クーポン配信の対象会員ID解決（クエリ抽象）。
 */
interface CouponDeliveryTargetRepository
{
    /** @return list<int> */
    public function findIdsByBirthdayMonth(int $projectId, int $month): array;

    /**
     * 指定したタグのいずれかが付いている会員。
     *
     * @param  list<int>  $tagIds
     * @return list<int>
     */
    public function findIdsByTagIds(int $projectId, array $tagIds): array;

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
     * @param  string  $mode  inactive_days|visited_within_days|days_after
     * @return list<int>
     */
    public function findIdsByLastVisited(int $projectId, string $mode, int $days, ?\DateTimeInterface $referenceDate = null): array;

    /** @return list<int> */
    public function findIdsBySex(int $projectId, string $sex): array;

    /** @return list<int> */
    public function findIdsByRank(int $projectId, string $rank): array;

    /**
     * スタンプ累計・達成で絞り込む。
     *
     * @return list<int>
     */
    public function findIdsByStampProgress(
        int $projectId,
        ?int $stampCardId,
        ?int $minStamps,
        bool $completedOnly,
    ): array;

    /**
     * 累計付与ポイントで絞り込む。
     *
     * @return list<int>
     */
    public function findIdsByLifetimeGrantedPoints(int $projectId, ?int $min, ?int $max): array;

    /**
     * 指定商品を購入した会員（アプリ注文またはスマレジPOS来店明細）。
     *
     * @return list<int>
     */
    public function findIdsByPurchasedProduct(int $projectId, int $productId): array;

    /**
     * 指定クーポンを保有し、使用状態が一致する会員。
     *
     * @param  string  $status  unused|used
     * @return list<int>
     */
    public function findIdsByCouponStatus(int $projectId, int $couponId, string $status): array;

    /**
     * プロフィール任意項目が指定値のいずれかに一致する会員。
     *
     * @param  list<string>  $values
     * @return list<int>
     */
    public function findIdsByProfileField(int $projectId, string $key, array $values, bool $requireAll = false): array;

    public function countAll(int $projectId): int;

    /** @return list<int> */
    public function findAllIds(int $projectId): array;

    /**
     * @param  list<int>  $memberIds
     * @return list<int>
     */
    public function filterValidIds(int $projectId, array $memberIds): array;
}
