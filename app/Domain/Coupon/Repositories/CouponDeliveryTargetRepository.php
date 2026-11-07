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
     * 指定クーポンの使用状態が一致する会員。unused は期限内のみ、not_issued は未保有の会員。
     *
     * @param  string  $status  unused|used|expired|not_issued
     * @return list<int>
     */
    public function findIdsByCouponStatus(int $projectId, int $couponId, string $status): array;

    /**
     * 指定お知らせを既読にした会員。unread は配信先（宛先なしの一斉配信は全会員）のうち未読の会員。
     *
     * @param  string  $status  read|unread
     * @return list<int>
     */
    public function findIdsByAnnouncementRead(int $projectId, int $announcementId, string $status): array;

    /**
     * 会員登録日（Y-m-d）が期間内の会員。片方だけの指定はその日以降／以前。
     *
     * @return list<int>
     */
    public function findIdsByRegisteredDate(int $projectId, ?string $from, ?string $to): array;

    /**
     * 誕生日から求めた今日時点の年齢で絞り込む。誕生日未登録の会員は含めない。
     *
     * @return list<int>
     */
    public function findIdsByAge(int $projectId, ?int $min, ?int $max): array;

    /**
     * 現在のポイント残高で絞り込む。
     *
     * @return list<int>
     */
    public function findIdsByPointBalance(int $projectId, ?int $min, ?int $max): array;

    /**
     * 指定店舗のいずれかに所属する会員。
     *
     * @param  list<int>  $storeIds
     * @return list<int>
     */
    public function findIdsByStores(int $projectId, array $storeIds): array;

    /**
     * アプリ（端末）の最終利用で絞り込む。inactive_days は端末を持つ会員のうち N 日以上使っていない会員。
     *
     * @param  string  $mode  within_days|inactive_days
     * @return list<int>
     */
    public function findIdsByAppLastUsed(int $projectId, string $mode, int $days): array;

    /**
     * 指定アンケートに回答した／していない会員。
     *
     * @param  string  $status  answered|unanswered
     * @return list<int>
     */
    public function findIdsBySurveyResponse(int $projectId, int $surveyId, string $status): array;

    /**
     * 直近 N 日以降（今後の予約を含む）にキャンセル以外の予約がある／ない会員。
     *
     * @param  string  $mode  has|none
     * @return list<int>
     */
    public function findIdsByReservation(int $projectId, string $mode, int $days): array;

    /**
     * 登録経路のいずれかから登録した会員。
     *
     * @param  list<int>  $sourceIds
     * @return list<int>
     */
    public function findIdsByAcquisitionSources(int $projectId, array $sourceIds): array;

    /**
     * サブスクの状態。active=継続中、ended=過去に加入して今は継続なし、none=加入したことがない。
     *
     * @return list<int>
     */
    public function findIdsBySubscriptionStatus(int $projectId, string $status): array;

    /**
     * 使える回数券（取消・使い切り・期限切れでない）を持っている／いない会員。
     *
     * @param  string  $status  active|none
     * @return list<int>
     */
    public function findIdsByTicketStatus(int $projectId, string $status): array;

    /**
     * クーポンを使った回数（累計）で絞り込む。
     *
     * @return list<int>
     */
    public function findIdsByCouponUseCount(int $projectId, ?int $min, ?int $max): array;

    /**
     * 直近 N 日以内に会員ランクが変わった会員。
     *
     * @return list<int>
     */
    public function findIdsByRankChangedWithin(int $projectId, int $days): array;

    /**
     * Apple Wallet に会員証を追加している／いない会員。
     *
     * @param  string  $status  registered|not_registered
     * @return list<int>
     */
    public function findIdsByWalletStatus(int $projectId, string $status): array;

    /**
     * 誕生日（月日）が今日から N 日後まで（within）／ちょうど N 日後（exact）の会員。
     *
     * @return list<int>
     */
    public function findIdsByBirthdaySoon(int $projectId, string $mode, int $days): array;

    /**
     * キャンセル以外の注文の累計金額で絞り込む。
     *
     * @return list<int>
     */
    public function findIdsByLifetimeSales(int $projectId, ?int $min, ?int $max): array;

    /**
     * 指定した種類の注文（キャンセル以外）をしたことがある会員。
     *
     * @param  list<string>  $orderTypes
     * @return list<int>
     */
    public function findIdsByOrderTypes(int $projectId, array $orderTypes): array;

    /**
     * 直近 N 日に順番待ち（キャンセル以外）を使った会員。
     *
     * @return list<int>
     */
    public function findIdsByQueueWithin(int $projectId, int $days): array;

    /**
     * 今日から N 日後までに期限が来るポイント付与を持つ会員の候補（使用済みかどうかは見ていない）。
     *
     * @return list<int>
     */
    public function findIdsWithPointsExpiringCandidates(int $projectId, int $days): array;

    /**
     * 今日から N 日後までに期限が切れる未使用クーポンを持つ会員。
     *
     * @return list<int>
     */
    public function findIdsByCouponExpiring(int $projectId, int $days): array;

    /**
     * まだ引いていない（期限内の）くじの抽選権を持っている／いない会員。
     *
     * @param  string  $status  pending|none
     * @return list<int>
     */
    public function findIdsByLotteryStatus(int $projectId, string $status): array;

    /**
     * 最新の NPS 回答が指定の区分の会員。promoter=9〜10、passive=7〜8、detractor=0〜6。
     *
     * @return list<int>
     */
    public function findIdsByNpsCategory(int $projectId, string $category): array;

    /**
     * 直近 N 日に LINE 友だち追加（ブロック解除を含む）をした会員。
     *
     * @return list<int>
     */
    public function findIdsByLineFollowWithin(int $projectId, int $days): array;

    /**
     * 通知設定（未設定は「すべて受け取る」）で絞り込む。
     *
     * @return list<int>
     */
    public function findIdsByNotificationPreference(int $projectId, string $preference): array;

    /**
     * LINE 連携済みの会員のうち、ブロック中／ブロックしていない会員。
     *
     * @param  string  $status  blocked|not_blocked
     * @return list<int>
     */
    public function findIdsByLineBlockStatus(int $projectId, string $status): array;

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
