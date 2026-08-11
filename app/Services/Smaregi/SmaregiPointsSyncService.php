<?php

namespace App\Services\Smaregi;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Repositories\OrderRepository;
use App\Jobs\SyncOrderPointsToSmaregiJob;
use App\Util\LogHelper;
use Illuminate\Support\Facades\Cache;

/**
 * 会員ポイント差分をスマレジへ同期する。
 * points は加算が正、取り消し・減算が負。
 *
 * 注文単位のネット同期は orders 上の完了・claim で冪等化する（Cache TTL に依存しない）。
 * 失敗時は outbound pending を立て、pending 中は inbound がローカルを巻き戻さない。
 * Cash 等の再送経路がない注文は、失敗時にキューでリトライする。
 */
class SmaregiPointsSyncService
{
    private const ORDER_CLAIM_TTL_MINUTES = 5;

    private const ORDER_CLAIM_MAX_ATTEMPTS = 3;

    private const ORDER_CLAIM_RETRY_USEC = 100000;

    private const OUTBOUND_PENDING_KEY_PREFIX = 'smaregi_points_outbound_pending:';

    public function __construct(
        private MemberRepository $memberRepository,
        private OrderRepository $orderRepository,
        private SmaregiApiService $smaregiApi,
    ) {}

    /**
     * 注文の used/earned ネット差分を1回だけスマレジへ送る。
     *
     * @return bool 完了（成功・スキップ・既同期）なら true。失敗・未連携・他プロセス処理中なら false
     */
    public function syncOrderNetPoints(int $orderId, int $projectId, int $userId): bool
    {
        if ($this->orderRepository->isSmaregiPointsSynced($orderId, $projectId)) {
            return true;
        }

        $claimedAt = $this->tryClaimOrderSync($orderId, $projectId);
        if ($claimedAt === null) {
            return $this->orderRepository->isSmaregiPointsSynced($orderId, $projectId);
        }

        try {
            return $this->runOrderNetPointsSync($orderId, $projectId, $userId);
        } finally {
            $this->orderRepository->releaseSmaregiPointsSyncClaim($orderId, $projectId, $claimedAt);
        }
    }

    /**
     * 同期を試み、未完了ならキューでリトライする（Cash 等の再送経路補完）。
     */
    public function syncOrderNetPointsWithRetry(int $orderId, int $projectId, int $userId): bool
    {
        try {
            $succeeded = $this->syncOrderNetPoints($orderId, $projectId, $userId);
            if ($succeeded) {
                return true;
            }

            if ($this->orderRepository->isSmaregiPointsSynced($orderId, $projectId)) {
                return true;
            }
        } catch (\Throwable $e) {
            LogHelper::error('[Smaregi] order points sync threw', [
                'order_id' => $orderId,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
        }

        SyncOrderPointsToSmaregiJob::dispatch($orderId, $projectId, $userId)
            ->delay(now()->addSeconds(30));

        return false;
    }

    private function tryClaimOrderSync(int $orderId, int $projectId): ?string
    {
        for ($attempt = 1; $attempt <= self::ORDER_CLAIM_MAX_ATTEMPTS; $attempt++) {
            if ($this->orderRepository->isSmaregiPointsSynced($orderId, $projectId)) {
                return null;
            }

            $claimedAt = $this->orderRepository->tryClaimSmaregiPointsSync(
                $orderId,
                $projectId,
                self::ORDER_CLAIM_TTL_MINUTES,
            );
            if ($claimedAt !== null) {
                return $claimedAt;
            }

            if ($attempt < self::ORDER_CLAIM_MAX_ATTEMPTS) {
                usleep(self::ORDER_CLAIM_RETRY_USEC);
            }
        }

        LogHelper::warning('[DomainEvent] smaregi order points sync claim busy', [
            'order_id' => $orderId,
            'project_id' => $projectId,
        ]);

        return null;
    }

    private function runOrderNetPointsSync(int $orderId, int $projectId, int $userId): bool
    {
        if ($this->orderRepository->isSmaregiPointsSynced($orderId, $projectId)) {
            return true;
        }

        $order = $this->orderRepository->findById($orderId, $projectId);
        if ($order === null) {
            return true;
        }

        $member = $this->memberRepository->findByUserId($userId, $projectId);
        if ($member === null) {
            $this->orderRepository->markSmaregiPointsSynced($orderId, $projectId);

            return true;
        }

        $netDelta = $order->pointsEarned() - $order->pointsUsed();
        if ($netDelta === 0) {
            $this->orderRepository->markSmaregiPointsSynced($orderId, $projectId);

            return true;
        }

        // 未連携・契約未設定は完了にしない（後から連携したときに再送できる）
        if ($member->smaregiCustomerId() === '') {
            return false;
        }

        $contractId = $this->smaregiApi->getContractIdForProject($member->projectId());
        if ($contractId === null || trim($contractId) === '') {
            return false;
        }

        $succeeded = $this->syncPointDelta(
            $member->id()->value(),
            $member->projectId(),
            $netDelta,
            $orderId,
        );

        if ($succeeded) {
            $this->orderRepository->markSmaregiPointsSynced($orderId, $projectId);
        }

        return $succeeded;
    }

    /**
     * @return bool 同期成功またはスキップ（顧客未連携・契約未設定等）なら true。API 失敗なら false
     */
    public function syncPointDelta(
        int $memberId,
        int $projectId,
        int $points,
        ?int $orderId,
        bool $isBestEffortSync = false,
    ): bool {
        if ($points === 0) {
            return true;
        }

        $member = $this->memberRepository->findById($memberId, $projectId);
        if ($member === null) {
            return true;
        }

        $smaregiCustomerId = $member->smaregiCustomerId();
        if ($smaregiCustomerId === '') {
            return true;
        }

        $contractId = $this->smaregiApi->getContractIdForProject($member->projectId());
        if ($contractId === null || trim($contractId) === '') {
            return true;
        }

        $result = $this->smaregiApi->syncCustomerPointsForProject(
            $member->projectId(),
            $smaregiCustomerId,
            $points,
        );

        if ($result !== null) {
            $this->clearOutboundPending($memberId);

            return true;
        }

        $this->markOutboundPending($memberId);

        if ($orderId !== null || $isBestEffortSync) {
            LogHelper::warning('[DomainEvent] smaregi points sync failed on point delta', [
                'member_id' => $memberId,
                'project_id' => $projectId,
                'order_id' => $orderId,
                'points' => $points,
                'is_best_effort' => $isBestEffortSync,
            ]);

            return false;
        }

        throw new \Exception('スマレジへのポイント反映に失敗しました。storage/logs/laravel.log の [Smaregi] を参照してください。');
    }

    public function hasOutboundPending(int $memberId): bool
    {
        return Cache::has(self::OUTBOUND_PENDING_KEY_PREFIX.$memberId);
    }

    public function markOutboundPending(int $memberId): void
    {
        // 成功するまで残す（TTL 切れで inbound がローカル先行を巻き戻さない）
        Cache::forever(self::OUTBOUND_PENDING_KEY_PREFIX.$memberId, 1);
    }

    public function clearOutboundPending(int $memberId): void
    {
        Cache::forget(self::OUTBOUND_PENDING_KEY_PREFIX.$memberId);
    }
}
