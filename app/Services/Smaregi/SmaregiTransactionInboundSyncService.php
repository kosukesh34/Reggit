<?php

namespace App\Services\Smaregi;

use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Smaregi\Services\SmaregiInboundTransactionClassifier;
use App\Domain\Smaregi\ValueObjects\SmaregiInboundTransaction;
use App\Enums\OrderType;
use App\Models\Member;
use App\Models\Order;
use App\Models\Scopes\ProjectScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * スマレジ POS の通常販売取引を来店・購入履歴・注文として取り込む。
 * 取消・返品時は付与済みポイント／スタンプ／来店／注文を巻き戻す。
 */
class SmaregiTransactionInboundSyncService
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
        private SmaregiInboundTransactionClassifier $classifier,
        private SmaregiContractProjectResolver $projectResolver,
        private SmaregiInboundVisitRecorder $visitRecorder,
        private SmaregiInboundAwardApplier $awardApplier,
        private SmaregiInboundOrderRecorder $orderRecorder,
        private OrderRepository $orderRepository,
    ) {}

    /**
     * @param  list<string>  $transactionHeadIds
     */
    public function syncByTransactionHeadIds(string $contractId, array $transactionHeadIds): void
    {
        $contractId = trim($contractId);
        if ($contractId === '' || $transactionHeadIds === []) {
            return;
        }

        foreach ($transactionHeadIds as $rawHeadId) {
            $headId = trim((string) $rawHeadId);
            if ($headId === '') {
                continue;
            }
            $this->syncOneTransaction($contractId, $headId);
        }
    }

    private function syncOneTransaction(string $contractId, string $headId): void
    {
        $transaction = $this->smaregiApi->getTransaction($contractId, $headId, true);
        if ($transaction === null) {
            Log::warning('[Smaregi] Inbound transaction sync: getTransaction failed', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        $inbound = $this->classifier->classify($transaction);

        if ($inbound->isCancelled()) {
            $this->handleCancelled($contractId, $headId, $inbound);

            return;
        }

        if ($inbound->isSkip()) {
            Log::info('[Smaregi] Inbound transaction sync skipped: not a normal sale', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
                'transaction_head_division' => $transaction['transactionHeadDivision'] ?? null,
                'cancel_division' => $transaction['cancelDivision'] ?? null,
            ]);

            return;
        }

        if ($inbound->customerId() === '') {
            Log::info('[Smaregi] Inbound transaction sync skipped: no customerId', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        if ($inbound->signedAmount() === null) {
            Log::info('[Smaregi] Inbound transaction sync skipped: no purchase amount', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        $visitedAt = $inbound->visitedAt();
        if ($visitedAt === null) {
            Log::warning('[Smaregi] Inbound transaction sync skipped: no transaction datetime', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        $members = $this->findMembers($contractId, $inbound->customerId());
        if ($members === []) {
            Log::info('[Smaregi] Inbound transaction sync: ローカル会員なし', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $inbound->customerId(),
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        foreach ($members as $member) {
            if ($this->isReggitOriginated((int) $member->project_id, $headId, $transaction)) {
                $this->linkOutboundHeadIdIfNeeded((int) $member->project_id, $headId, $transaction);
                Log::info('[Smaregi] Inbound transaction sync skipped: Reggit-originated', [
                    'project_id' => (int) $member->project_id,
                    'transaction_head_id' => $headId,
                ]);

                continue;
            }

            if ($inbound->isReturn()) {
                $this->applyReturn($member, $headId, $inbound, $visitedAt);
            } else {
                $this->applySale($member, $headId, $inbound, $visitedAt);
            }
        }
    }

    private function handleCancelled(
        string $contractId,
        string $headId,
        SmaregiInboundTransaction $inbound,
    ): void {
        if ($inbound->customerId() === '') {
            Log::info('[Smaregi] Inbound transaction cancel skipped: no customerId', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        $members = $this->findMembers($contractId, $inbound->customerId());
        if ($members === []) {
            return;
        }

        foreach ($members as $member) {
            if ($this->orderRepository->existsBySmaregiTransactionHeadId((int) $member->project_id, $headId)) {
                $order = Order::query()
                    ->withoutGlobalScope(ProjectScope::class)
                    ->where('project_id', $member->project_id)
                    ->where('smaregi_transaction_head_id', $headId)
                    ->first();
                if ($order !== null && $order->order_type !== OrderType::POS) {
                    // Reggit outbound 取引の取消はアプリ側注文を触らない
                    continue;
                }
            }

            $this->awardApplier->reverseForCancel($member, $headId);
            $this->visitRecorder->deleteForTransaction($member, $headId);
            $this->orderRecorder->cancelByTransactionHeadId((int) $member->project_id, $headId);
            Log::info('[Smaregi] Inbound transaction cancel reversed', [
                'member_id' => (int) $member->id,
                'project_id' => (int) $member->project_id,
                'transaction_head_id' => $headId,
            ]);
        }
    }

    private function applySale(
        Member $member,
        string $headId,
        SmaregiInboundTransaction $inbound,
        Carbon $visitedAt,
    ): void {
        $amount = (float) $inbound->signedAmount();
        $visit = $this->visitRecorder->record(
            $member,
            $headId,
            $visitedAt,
            $amount,
            $inbound->details(),
        );
        $this->awardApplier->awardForPurchase(
            $member,
            $amount,
            $headId,
            $inbound->hasSmaregiManagedPointDelta(),
        );
        $order = $this->orderRecorder->recordSale(
            $member,
            $headId,
            $amount,
            $inbound->details(),
            $visitedAt,
        );
        if ($visit !== null && $order !== null) {
            $this->visitRecorder->attachOrder($visit, (int) $order->id);
        }
    }

    private function applyReturn(
        Member $member,
        string $headId,
        SmaregiInboundTransaction $inbound,
        Carbon $visitedAt,
    ): void {
        $this->visitRecorder->record(
            $member,
            $headId,
            $visitedAt,
            -1 * $inbound->absAmount(),
            $inbound->details(),
        );
        $this->awardApplier->reverseForReturn(
            $member,
            $inbound->absAmount(),
            $headId,
            $inbound->hasSmaregiManagedPointDelta(),
        );
        $this->orderRecorder->cancelByTransactionHeadId((int) $member->project_id, $headId);
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function isReggitOriginated(int $projectId, string $headId, array $transaction): bool
    {
        if ($this->orderRepository->findIdBySmaregiLayawayHeadId($projectId, $headId) !== null) {
            return true;
        }

        if ($this->orderRepository->existsBySmaregiTransactionHeadId($projectId, $headId)) {
            $order = Order::query()
                ->withoutGlobalScope(ProjectScope::class)
                ->where('project_id', $projectId)
                ->where('smaregi_transaction_head_id', $headId)
                ->first();

            return $order !== null && $order->order_type !== OrderType::POS;
        }

        $orderId = SmaregiOrderTransactionOutboundService::orderIdFromTerminalTranId(
            isset($transaction['terminalTranId']) ? (string) $transaction['terminalTranId'] : null,
        );
        if ($orderId === null) {
            return false;
        }

        return Order::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->where('order_type', '!=', OrderType::POS)
            ->exists();
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function linkOutboundHeadIdIfNeeded(int $projectId, string $headId, array $transaction): void
    {
        if ($this->orderRepository->existsBySmaregiTransactionHeadId($projectId, $headId)) {
            return;
        }

        $orderId = SmaregiOrderTransactionOutboundService::orderIdFromTerminalTranId(
            isset($transaction['terminalTranId']) ? (string) $transaction['terminalTranId'] : null,
        );
        if ($orderId === null) {
            return;
        }

        if (! $this->orderRepository->isSmaregiTransactionSynced($orderId, $projectId)) {
            $this->orderRepository->markSmaregiTransactionSynced($orderId, $projectId, $headId);
        }
    }

    /**
     * @return list<Member>
     */
    private function findMembers(string $contractId, string $smaregiCustomerId): array
    {
        $projectIds = $this->projectResolver->projectIdsForContract($contractId);
        if ($projectIds === []) {
            return [];
        }

        return Member::query()
            ->whereIn('project_id', $projectIds)
            ->where('smaregi_customer_id', $smaregiCustomerId)
            ->get()
            ->all();
    }
}
