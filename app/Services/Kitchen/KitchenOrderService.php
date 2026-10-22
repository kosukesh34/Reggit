<?php

namespace App\Services\Kitchen;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\KitchenDevice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Admin\OrderAdminService;
use App\Support\DailySequenceNumber;
use App\Util\StoreContext;
use Carbon\Carbon;
use InvalidArgumentException;

class KitchenOrderService
{
    /** @var list<string> */
    private const ACTIVE_STATUSES = [
        OrderStatus::CONFIRMED,
        OrderStatus::PREPARING,
        OrderStatus::READY,
    ];

    /** @var list<string> */
    private const ALLOWED_UPDATES = [
        OrderStatus::PREPARING,
        OrderStatus::READY,
        OrderStatus::COMPLETED,
    ];

    public function __construct(
        private OrderAdminService $orderAdminService,
        private StoreContext $storeContext,
    ) {}

    /**
     * @return list<array{
     *     id: int,
     *     order_number: string,
     *     status: string,
     *     order_type: string|null,
     *     notes: string|null,
     *     created_at: string|null,
     *     items: list<array{name: string, quantity: int, notes: string|null}>
     * }>
     */
    public function listActiveOrders(int $projectId): array
    {
        $orders = Order::query()
            ->with(['items.product'])
            ->where('project_id', $projectId)
            ->whereNotIn('order_type', [OrderType::SHIPPING, OrderType::TICKET, OrderType::SUBSCRIPTION])
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->orderBy('created_at')
            ->get();

        $result = [];
        foreach ($orders as $order) {
            $result[] = $this->toKitchenArray($order);
        }

        return $result;
    }

    public function updateStatus(int $projectId, int $orderId, string $status): array
    {
        if (in_array($status, self::ALLOWED_UPDATES, true) === false) {
            throw new InvalidArgumentException('更新できないステータスです');
        }

        $order = Order::query()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->firstOrFail();

        if ($status === OrderStatus::COMPLETED) {
            $this->orderAdminService->complete($orderId);
        } else {
            $this->orderAdminService->updateStatus($orderId, $status);
        }

        $fresh = Order::query()->with(['items.product'])->whereKey($orderId)->where('project_id', $projectId)->firstOrFail();

        return $this->toKitchenArray($fresh);
    }

    public function registerDevice(int $projectId, string $pushToken, string $platform = 'ios', ?string $deviceName = null): KitchenDevice
    {
        $token = trim($pushToken);
        if ($token === '') {
            throw new InvalidArgumentException('push_token が空です');
        }

        $device = KitchenDevice::query()->updateOrCreate(
            [
                'project_id' => $projectId,
                'push_token' => $token,
            ],
            [
                'store_id' => $this->storeContext->resolveForWrite($projectId),
                'platform' => trim($platform) !== '' ? trim($platform) : 'ios',
                'device_name' => $deviceName !== null ? trim($deviceName) : null,
                'last_seen_at' => Carbon::now(),
            ]
        );

        return $device;
    }

    public function unregisterDevice(int $projectId, string $pushToken): void
    {
        $token = trim($pushToken);
        if ($token === '') {
            return;
        }
        KitchenDevice::query()
            ->where('project_id', $projectId)
            ->where('push_token', $token)
            ->where('store_id', $this->storeContext->resolveForWrite($projectId))
            ->delete();
    }

    /**
     * @return array{
     *     id: int,
     *     order_number: string,
     *     status: string,
     *     order_type: string|null,
     *     notes: string|null,
     *     created_at: string|null,
     *     items: list<array{name: string, quantity: int, notes: string|null}>
     * }
     */
    private function toKitchenArray(Order $order): array
    {
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'name' => $this->resolveItemName($item),
                'quantity' => (int) ($item->quantity ?? 0),
                'notes' => $item->notes !== null ? (string) $item->notes : null,
            ];
        }

        return [
            'id' => (int) $order->id,
            'order_number' => DailySequenceNumber::formatStoredForDisplay($order->order_number),
            'status' => (string) $order->status,
            'order_type' => $order->order_type !== null ? (string) $order->order_type : null,
            'notes' => $order->notes !== null ? (string) $order->notes : null,
            'created_at' => $order->created_at?->toIso8601String(),
            'items' => $items,
        ];
    }

    private function resolveItemName(OrderItem $item): string
    {
        $snapshot = trim((string) ($item->product_name ?? ''));
        if ($snapshot !== '') {
            return $snapshot;
        }
        $fromProduct = trim((string) ($item->product?->name ?? ''));
        if ($fromProduct !== '') {
            return $fromProduct;
        }

        return '商品';
    }
}
