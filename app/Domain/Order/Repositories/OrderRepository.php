<?php

namespace App\Domain\Order\Repositories;

use App\Domain\Order\Aggregates\Order;
use App\Domain\Order\ValueObjects\OrderLineItem;
use App\Domain\Order\ValueObjects\PendingOrder;

interface OrderRepository
{
    public function findById(int $orderId, int $projectId): ?Order;

    public function findByIdForUpdate(int $orderId, int $projectId): ?Order;

    /** Webhook 等、テナント不明のシステム経路用。PK はグローバル一意。 */
    public function findByPrimaryKey(int $orderId): ?Order;

    public function findByPrimaryKeyForUpdate(int $orderId): ?Order;

    public function findByIdAndUserId(int $orderId, int $userId, int $projectId): ?Order;

    public function findByIdAndUserIdForUpdate(int $orderId, int $userId, int $projectId): ?Order;

    public function save(Order $order): void;

    /**
     * @param  list<OrderLineItem>  $lineItems
     */
    public function persistPending(PendingOrder $pendingOrder, array $lineItems): int;

    public function isSmaregiPointsSynced(int $orderId, int $projectId): bool;

    /**
     * @return string|null claim 時刻（Y-m-d H:i:s）。取得失敗時は null
     */
    public function tryClaimSmaregiPointsSync(int $orderId, int $projectId, int $claimTtlMinutes): ?string;

    public function markSmaregiPointsSynced(int $orderId, int $projectId): void;

    /** 自分の claim 時刻と一致するときだけ解放する（他プロセスの claim を消さない） */
    public function releaseSmaregiPointsSyncClaim(int $orderId, int $projectId, string $claimedAt): void;

    public function hasUnsyncedSmaregiPointsForUser(int $userId, int $projectId): bool;

    /**
     * スマレジポイント未同期の注文を返す（Cash 失敗・未連携後の再送用）。
     *
     * @return list<array{orderId: int, projectId: int, userId: int}>
     */
    public function findUnsyncedSmaregiPointsOrdersForUser(int $userId, int $projectId): array;

    public function isSmaregiTransactionSynced(int $orderId, int $projectId): bool;

    /**
     * @return string|null claim 時刻（Y-m-d H:i:s）。取得失敗時は null
     */
    public function tryClaimSmaregiTransactionSync(int $orderId, int $projectId, int $claimTtlMinutes): ?string;

    public function markSmaregiTransactionSynced(
        int $orderId,
        int $projectId,
        string $smaregiTransactionHeadId,
    ): void;

    public function releaseSmaregiTransactionSyncClaim(int $orderId, int $projectId, string $claimedAt): void;

    public function existsBySmaregiTransactionHeadId(int $projectId, string $smaregiTransactionHeadId): bool;

    public function findIdBySmaregiTransactionHeadId(int $projectId, string $smaregiTransactionHeadId): ?int;

    public function hasSmaregiLayaway(int $orderId, int $projectId): bool;

    public function markSmaregiLayawayRegistered(
        int $orderId,
        int $projectId,
        string $smaregiLayawayHeadId,
    ): void;

    public function findIdBySmaregiLayawayHeadId(int $projectId, string $smaregiLayawayHeadId): ?int;
}
