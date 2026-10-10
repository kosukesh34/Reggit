<?php

namespace App\Services\Smaregi;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Settings\Repositories\SmaregiSettings;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\SmaregiCancelDivision;
use App\Enums\SmaregiTransactionDetailDivision;
use App\Enums\SmaregiTransactionHeadDivision;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Scopes\ProjectScope;
use App\Models\Store;
use App\Util\LogHelper;
use Illuminate\Support\Carbon;

/**
 * Reggit 注文をスマレジ POS 取引として登録する。
 * Stripe は通常販売、現金は取置き→完了時に引取（通常販売）で確定する。
 * ポイントは point/add 同期側に任せ、取引 body には載せない。
 */
class SmaregiOrderTransactionOutboundService
{
    private const ORDER_CLAIM_TTL_MINUTES = 5;

    private const ORDER_CLAIM_MAX_ATTEMPTS = 3;

    private const ORDER_CLAIM_RETRY_USEC = 100000;

    public function __construct(
        private OrderRepository $orderRepository,
        private MemberRepository $memberRepository,
        private SmaregiApiService $smaregiApi,
        private SmaregiSettings $shopSettingRepository,
    ) {}

    /**
     * 注文作成／決済確定時の同期。
     * 現金は取置き登録まで、Stripe は通常販売まで行う。
     *
     * @return bool 完了（成功・スキップ・既同期）なら true
     */
    public function syncOrderTransaction(int $orderId, int $projectId, int $userId): bool
    {
        if ($this->orderRepository->isSmaregiTransactionSynced($orderId, $projectId)) {
            return true;
        }

        $order = $this->loadOrder($orderId, $projectId);
        if ($order === null) {
            return true;
        }
        if ($order->order_type === OrderType::POS || $order->status === OrderStatus::CANCELLED) {
            return true;
        }
        if ($this->isCashOrder($order) && $this->orderRepository->hasSmaregiLayaway($orderId, $projectId)) {
            return true;
        }

        $claimedAt = $this->tryClaimOrderSync($orderId, $projectId);
        if ($claimedAt === null) {
            return $this->isCreatePhaseDone($orderId, $projectId, $order);
        }

        try {
            return $this->runCreatePhaseSync($order, $userId);
        } finally {
            $this->orderRepository->releaseSmaregiTransactionSyncClaim($orderId, $projectId, $claimedAt);
        }
    }

    public function syncOrderTransactionWithRetry(int $orderId, int $projectId, int $userId): bool
    {
        $pending = Order::withoutGlobalScopes()->whereKey($orderId)->where('project_id', $projectId)
            ->where('smaregi_create_pending', true);
        $lease = now()->addMinutes(5);
        if ((clone $pending)->where('smaregi_create_retry_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('smaregi_create_leased_until')->orWhere('smaregi_create_leased_until', '<=', now()))
            ->update(['smaregi_create_leased_until' => $lease]) === 0) {
            return $this->isCreatePhaseDone($orderId, $projectId, $this->loadOrder($orderId, $projectId));
        }
        try {
            $order = $this->loadOrder($orderId, $projectId);
            $configured = trim((string) $this->smaregiApi->getContractIdForProject($projectId)) !== '';
            $succeeded = $order === null || $order->status === OrderStatus::CANCELLED
                || $order->order_type === OrderType::POS
                || $this->isCreatePhaseDone($orderId, $projectId, $order)
                || ($configured && $this->syncOrderTransaction($orderId, $projectId, $userId));
            (clone $pending)->where('smaregi_create_leased_until', $lease)->update($succeeded ? [
                'smaregi_create_pending' => false, 'smaregi_create_retry_at' => null, 'smaregi_create_leased_until' => null,
            ] : ['smaregi_create_leased_until' => null, 'smaregi_create_retry_at' => $configured ? now()->addMinute() : now()->addHour()]);

            return $succeeded;
        } catch (\Throwable $exception) {
            (clone $pending)->where('smaregi_create_leased_until', $lease)
                ->update(['smaregi_create_leased_until' => null, 'smaregi_create_retry_at' => now()->addMinute()]);
            report($exception);

            return false;
        }
    }

    /**
     * 現金取置きを通常販売（引取）として確定する。管理画面の注文完了時に呼ぶ。
     *
     * @return bool 完了（成功・スキップ・既同期）なら true
     */
    public function finalizeCashLayaway(int $orderId, int $projectId, int $userId): bool
    {
        if ($this->orderRepository->isSmaregiTransactionSynced($orderId, $projectId)) {
            return true;
        }

        $order = $this->loadOrder($orderId, $projectId);
        if ($order === null) {
            return true;
        }
        if ($order->order_type === OrderType::POS) {
            return true;
        }
        if (! $this->isCashOrder($order)) {
            return $this->syncOrderTransaction($orderId, $projectId, $userId);
        }

        $claimedAt = $this->tryClaimOrderSync($orderId, $projectId);
        if ($claimedAt === null) {
            return $this->orderRepository->isSmaregiTransactionSynced($orderId, $projectId);
        }

        try {
            return $this->runFinalizeCashLayaway($order, $userId);
        } finally {
            $this->orderRepository->releaseSmaregiTransactionSyncClaim($orderId, $projectId, $claimedAt);
        }
    }

    public function finalizeCashLayawayWithRetry(int $orderId, int $projectId, int $userId): bool
    {
        // The order transition saved this request atomically. Claim it before any API call.
        $pending = Order::withoutGlobalScopes()->whereKey($orderId)->where('project_id', $projectId)
            ->where('smaregi_pickup_pending', true);
        $lease = now()->addMinutes(5);
        if ((clone $pending)->where('smaregi_pickup_retry_at', '<=', now())
            ->update(['smaregi_pickup_retry_at' => $lease]) === 0) {
            return $this->orderRepository->isSmaregiTransactionSynced($orderId, $projectId);
        }

        try {
            // Keep unconfigured optional integrations pending without sending requests.
            $configured = trim((string) $this->smaregiApi->getContractIdForProject($projectId)) !== '';
            $succeeded = $this->orderRepository->isSmaregiTransactionSynced($orderId, $projectId)
                || ($configured && $this->finalizeCashLayaway($orderId, $projectId, $userId));
            (clone $pending)->where('smaregi_pickup_retry_at', $lease)->update($succeeded ? [
                'smaregi_pickup_pending' => false, 'smaregi_pickup_retry_at' => null,
            ] : [
                'smaregi_pickup_retry_at' => $configured ? now()->addMinute() : now()->addHour(),
            ]);

            return $succeeded;
        } catch (\Throwable $exception) {
            (clone $pending)->where('smaregi_pickup_retry_at', $lease)
                ->update(['smaregi_pickup_retry_at' => now()->addMinute()]);
            report($exception);

            return false;
        }
    }

    public static function terminalTranIdForOrder(int $orderId): string
    {
        return 'RG'.$orderId;
    }

    public static function terminalTranIdForLayawayPickup(int $orderId): string
    {
        return 'RGP'.$orderId;
    }

    public static function orderIdFromTerminalTranId(?string $terminalTranId): ?int
    {
        $value = trim((string) $terminalTranId);
        if (preg_match('/^RGP?(\d+)$/', $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function isCreatePhaseDone(int $orderId, int $projectId, ?Order $order): bool
    {
        if ($this->orderRepository->isSmaregiTransactionSynced($orderId, $projectId)) {
            return true;
        }
        if ($order !== null && $this->isCashOrder($order)) {
            return $this->orderRepository->hasSmaregiLayaway($orderId, $projectId);
        }

        return false;
    }

    private function isCashOrder(Order $order): bool
    {
        return $order->payment_method === PaymentMethod::CASH;
    }

    private function loadOrder(int $orderId, int $projectId): ?Order
    {
        return Order::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->with(['items.product'])
            ->first();
    }

    private function tryClaimOrderSync(int $orderId, int $projectId): ?string
    {
        for ($attempt = 1; $attempt <= self::ORDER_CLAIM_MAX_ATTEMPTS; $attempt++) {
            if ($this->orderRepository->isSmaregiTransactionSynced($orderId, $projectId)) {
                return null;
            }

            $claimedAt = $this->orderRepository->tryClaimSmaregiTransactionSync(
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

        LogHelper::warning('[Smaregi] order transaction sync claim busy', [
            'order_id' => $orderId,
            'project_id' => $projectId,
        ]);

        return null;
    }

    private function runCreatePhaseSync(Order $order, int $userId): bool
    {
        $orderId = (int) $order->id;
        $projectId = (int) $order->project_id;

        if ($this->orderRepository->isSmaregiTransactionSynced($orderId, $projectId)) {
            return true;
        }
        if ($this->isCashOrder($order) && $this->orderRepository->hasSmaregiLayaway($orderId, $projectId)) {
            return true;
        }

        // Stripe 未決済はスマレジへ送らない（決済完了前の Webhook 往復を防ぐ）
        if ($order->payment_method === PaymentMethod::STRIPE && $order->status === OrderStatus::PENDING) {
            LogHelper::info('[Smaregi] order transaction sync skipped: stripe pending', [
                'order_id' => $orderId,
                'project_id' => $projectId,
            ]);

            return true;
        }

        $context = $this->resolveSyncContext($order, $userId);
        if ($context === null) {
            return false;
        }

        if ($this->isCashOrder($order)) {
            return $this->createLayawayTransaction($order, $userId, $context);
        }

        return $this->createSaleTransaction($order, $userId, $context, null);
    }

    private function runFinalizeCashLayaway(Order $order, int $userId): bool
    {
        $orderId = (int) $order->id;
        $projectId = (int) $order->project_id;

        if ($this->orderRepository->isSmaregiTransactionSynced($orderId, $projectId)) {
            return true;
        }

        $layawayHeadId = trim((string) ($order->smaregi_layaway_head_id ?? ''));
        $context = $this->resolveSyncContext($order, $userId);
        if ($context === null) {
            return false;
        }

        if ($layawayHeadId === '') {
            if (! $this->createLayawayTransaction($order, $userId, $context)) {
                return false;
            }
            $order->refresh();
            $layawayHeadId = trim((string) ($order->smaregi_layaway_head_id ?? ''));
            if ($layawayHeadId === '') {
                return false;
            }
        }

        return $this->createSaleTransaction($order, $userId, $context, $layawayHeadId);
    }

    /**
     * @return array{contractId: string, storeId: string, terminalId: string, details: list<array<string, string>>}|null
     */
    private function resolveSyncContext(Order $order, int $userId): ?array
    {
        $orderId = (int) $order->id;
        $projectId = (int) $order->project_id;

        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if ($contractId === null || trim($contractId) === '') {
            return null;
        }

        $store = Store::where('project_id', $projectId)->find($order->store_id);
        $multipleStores = Store::where('project_id', $projectId)->count() > 1;
        $storeId = $store?->smaregi_store_id ?: ($multipleStores ? null : $this->resolveStoreId($projectId, $contractId));
        $terminalId = $store?->smaregi_terminal_id ?: ($multipleStores && $storeId !== null ? $this->smaregiApi->getFirstTerminalId($contractId, $storeId) : $this->resolveTerminalId($projectId, $contractId, $storeId));
        if ($storeId === null || $terminalId === null) {
            LogHelper::warning('[Smaregi] order transaction sync skipped: store/terminal missing', [
                'order_id' => $orderId,
                'project_id' => $projectId,
                'store_id' => $storeId,
                'terminal_id' => $terminalId,
            ]);

            return null;
        }

        $details = $this->buildDetails($order);
        if ($details === null) {
            LogHelper::warning('[Smaregi] order transaction sync skipped: unmapped products', [
                'order_id' => $orderId,
                'project_id' => $projectId,
            ]);

            return null;
        }
        if ($details === []) {
            LogHelper::warning('[Smaregi] order transaction sync skipped: empty details', [
                'order_id' => $orderId,
                'project_id' => $projectId,
            ]);

            return null;
        }

        return [
            'contractId' => $contractId,
            'storeId' => $storeId,
            'terminalId' => $terminalId,
            'details' => $details,
        ];
    }

    /**
     * @param  array{contractId: string, storeId: string, terminalId: string, details: list<array<string, string>>}  $context
     */
    private function createLayawayTransaction(Order $order, int $userId, array $context): bool
    {
        $orderId = (int) $order->id;
        $projectId = (int) $order->project_id;
        $total = (int) round((float) $order->total_amount);
        $body = [
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::LAYAWAY,
            'subtotal' => (string) $total,
            'total' => (string) $total,
            'taxInclude' => '0',
            'taxExclude' => '0',
            'storeId' => $context['storeId'],
            'terminalId' => $context['terminalId'],
            'terminalTranId' => self::terminalTranIdForOrder($orderId),
            'terminalTranDateTime' => Carbon::now('Asia/Tokyo')->format('Y-m-d\TH:i:sP'),
            'details' => $context['details'],
            'memo' => 'Reggit cash order (layaway) #'.$orderId,
        ];

        $member = $this->memberRepository->findByUserId($userId, $projectId);
        if ($member !== null && $member->smaregiCustomerId() !== '') {
            $body['customerId'] = $member->smaregiCustomerId();
        }

        $result = $this->smaregiApi->createLayaway($context['contractId'], $body);
        if ($result === null) {
            LogHelper::warning('[Smaregi] order layaway sync failed', [
                'order_id' => $orderId,
                'project_id' => $projectId,
            ]);

            return false;
        }

        $headId = trim((string) ($result['transactionHeadId'] ?? $result['transaction_head_id'] ?? ''));
        if ($headId === '') {
            LogHelper::warning('[Smaregi] order layaway sync: missing transactionHeadId', [
                'order_id' => $orderId,
                'project_id' => $projectId,
            ]);

            return false;
        }

        $this->orderRepository->markSmaregiLayawayRegistered($orderId, $projectId, $headId);

        LogHelper::info('[Smaregi] order layaway registered', [
            'order_id' => $orderId,
            'project_id' => $projectId,
            'layaway_head_id' => $headId,
        ]);

        return true;
    }

    /**
     * @param  array{contractId: string, storeId: string, terminalId: string, details: list<array<string, string>>}  $context
     */
    private function createSaleTransaction(
        Order $order,
        int $userId,
        array $context,
        ?string $layawayHeadId,
    ): bool {
        $orderId = (int) $order->id;
        $projectId = (int) $order->project_id;
        $total = (int) round((float) $order->total_amount);
        $isPickup = $layawayHeadId !== null && $layawayHeadId !== '';

        $body = [
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'subtotal' => (string) $total,
            'total' => (string) $total,
            'taxInclude' => '0',
            'taxExclude' => '0',
            'storeId' => $context['storeId'],
            'terminalId' => $context['terminalId'],
            'terminalTranId' => $isPickup
                ? self::terminalTranIdForLayawayPickup($orderId)
                : self::terminalTranIdForOrder($orderId),
            'terminalTranDateTime' => Carbon::now('Asia/Tokyo')->format('Y-m-d\TH:i:sP'),
            'deposit' => (string) $total,
            'depositCash' => (string) $total,
            'change' => '0',
            'details' => $context['details'],
            'memo' => $isPickup
                ? 'Reggit cash order pickup #'.$orderId
                : 'Reggit order #'.$orderId,
        ];

        if ($isPickup) {
            $body['layawayServerTransactionHeadId'] = $layawayHeadId;
        }

        $member = $this->memberRepository->findByUserId($userId, $projectId);
        if ($member !== null && $member->smaregiCustomerId() !== '') {
            $body['customerId'] = $member->smaregiCustomerId();
        }

        $result = $this->smaregiApi->createTransaction($context['contractId'], $body);
        if ($result === null) {
            LogHelper::warning('[Smaregi] order transaction sync failed', [
                'order_id' => $orderId,
                'project_id' => $projectId,
                'is_layaway_pickup' => $isPickup,
            ]);

            return false;
        }

        $headId = trim((string) ($result['transactionHeadId'] ?? $result['transaction_head_id'] ?? ''));
        if ($headId === '') {
            LogHelper::warning('[Smaregi] order transaction sync: missing transactionHeadId', [
                'order_id' => $orderId,
                'project_id' => $projectId,
            ]);

            return false;
        }

        $this->orderRepository->markSmaregiTransactionSynced($orderId, $projectId, $headId);

        LogHelper::info('[Smaregi] order transaction synced', [
            'order_id' => $orderId,
            'project_id' => $projectId,
            'transaction_head_id' => $headId,
            'is_layaway_pickup' => $isPickup,
        ]);

        return true;
    }

    private function resolveStoreId(int $projectId, string $contractId): ?string
    {
        $configured = trim((string) $this->shopSettingRepository->getValue('smaregi_store_id', '', $projectId));
        if ($configured !== '') {
            return $configured;
        }

        return $this->smaregiApi->getFirstStoreId($contractId);
    }

    private function resolveTerminalId(int $projectId, string $contractId, ?string $storeId): ?string
    {
        $configured = trim((string) $this->shopSettingRepository->getValue('smaregi_terminal_id', '', $projectId));
        if ($configured !== '') {
            return $configured;
        }

        return $this->smaregiApi->getFirstTerminalId($contractId, $storeId);
    }

    /**
     * @return list<array<string, string>>|null null = 未連携商品あり
     */
    private function buildDetails(Order $order): ?array
    {
        $details = [];
        $detailId = 1;
        /** @var OrderItem $item */
        foreach ($order->items as $item) {
            $smaregiProductId = trim((string) ($item->product?->smaregi_product_id ?? ''));
            if ($smaregiProductId === '') {
                return null;
            }

            $price = (int) round((float) $item->price);
            $details[] = [
                'transactionDetailId' => (string) $detailId,
                'transactionDetailDivision' => SmaregiTransactionDetailDivision::NORMAL,
                'productId' => $smaregiProductId,
                'productName' => (string) ($item->product?->name ?? $item->product_name ?? ''),
                'price' => (string) $price,
                'salesPrice' => (string) $price,
                'quantity' => (string) (int) $item->quantity,
            ];
            $detailId++;
        }

        return $details;
    }
}
