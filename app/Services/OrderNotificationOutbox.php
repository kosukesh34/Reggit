<?php

namespace App\Services;

use App\Domain\Order\Events\OrderCompleted;
use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Events\OrderReady;
use App\Domain\Order\Events\OrderShipped;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Project;
use App\Models\Store;
use App\Services\Kitchen\KitchenPushService;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Durable, at-least-once notification delivery with independent channel retries. */
class OrderNotificationOutbox
{
    private const TABLE = 'order_notification_outbox';

    public function __construct(
        private OrderNotificationService $notifications,
        private KitchenPushService $kitchen,
        private StoreContext $storeContext,
    ) {}

    /** Call inside the transaction that changes the order state. */
    public function record(object $event): void
    {
        $name = $this->eventName($event);
        if ($name === null) {
            return;
        }
        $order = Order::withoutGlobalScopes()->where('project_id', $event->projectId)->findOrFail($event->orderId);
        $channels = $name === 'confirmed' ? ['native', 'line', 'kitchen'] : (in_array($name, ['ready', 'shipped'], true) ? ['native', 'line'] : ['native']);
        if ($order->order_type === OrderType::SHIPPING && in_array($name, ['confirmed', 'shipped'], true)) {
            // 配送注文は厨房に出さず、購入者へメールでも知らせる
            $channels = ['native', 'line', 'email'];
        }
        foreach ($channels as $channel) {
            DB::table(self::TABLE)->upsert([[
                'project_id' => $event->projectId,
                'store_id' => $order->store_id,
                'order_id' => $event->orderId,
                'event' => $name,
                'channel' => $channel,
                'available_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]], ['order_id', 'event', 'channel'], ['order_id']);
        }
    }

    public function deliverEvent(object $event): void
    {
        $name = $this->eventName($event);
        if ($name === null) {
            return;
        }
        $ids = DB::table(self::TABLE)->where('project_id', $event->projectId)
            ->where('order_id', $event->orderId)->where('event', $name)->pluck('id');
        foreach ($ids as $id) {
            $this->deliver((int) $id);
        }
    }

    public function drain(int $limit = 100): int
    {
        // Explicit cross-project worker query; each delivery binds its saved project/store.
        $ids = DB::table(self::TABLE)->whereNull('delivered_at')->where('available_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('leased_until')->orWhere('leased_until', '<=', now()))
            ->orderBy('id')->limit($limit)->pluck('id');
        foreach ($ids as $id) {
            $this->deliver((int) $id);
        }

        return $ids->count();
    }

    private function deliver(int $id): void
    {
        $lease = bin2hex(random_bytes(16));
        $delivery = DB::transaction(function () use ($id, $lease) {
            $row = DB::table(self::TABLE)->where('id', $id)->lockForUpdate()->first();
            if ($row === null || $row->delivered_at !== null || now()->lt($row->available_at)
                || ($row->leased_until !== null && now()->lt($row->leased_until))) {
                return null;
            }
            DB::table(self::TABLE)->where('id', $id)->update([
                'lease' => $lease, 'leased_until' => now()->addMinutes(5),
                'attempts' => $row->attempts + 1, 'updated_at' => now(),
            ]);

            return $row;
        });
        if ($delivery === null) {
            return;
        }

        $previous = AdminContext::getCurrentProject();
        try {
            AdminContext::setCurrentProject(Project::findOrFail($delivery->project_id));
            $store = Store::where('project_id', $delivery->project_id)->findOrFail($delivery->store_id);
            $this->storeContext->runForStore($store, function () use ($delivery): void {
                $order = Order::where('project_id', $delivery->project_id)->find($delivery->order_id);
                // Retried instructions must still describe an actionable order.
                // Finish obsolete rows so a cancelled/completed order is not retried forever.
                if ($order === null || $order->status === OrderStatus::CANCELLED
                    || ($delivery->event !== 'completed' && $order->status === OrderStatus::COMPLETED)
                    || ($delivery->event === 'ready' && $order->status !== OrderStatus::READY)
                    || ($delivery->event === 'shipped' && $order->status !== OrderStatus::SHIPPED)
                    || ($delivery->channel === 'kitchen' && ! in_array($order->status, [OrderStatus::CONFIRMED, OrderStatus::PREPARING], true))) {
                    return;
                }
                if ($delivery->channel === 'kitchen') {
                    $this->kitchen->notifyNewOrder($delivery->order_id, $delivery->project_id);

                    return;
                }
                if ($delivery->channel === 'email') {
                    $this->notifications->emailShopOrder($delivery->order_id, $delivery->project_id, $delivery->event);

                    return;
                }
                match ($delivery->event) {
                    'confirmed' => $this->notifications->notifyConfirmed($delivery->order_id, $delivery->project_id, $delivery->channel),
                    'ready' => $this->notifications->notifyReady($delivery->order_id, $delivery->project_id, $delivery->channel),
                    'shipped' => $this->notifications->notifyShipped($delivery->order_id, $delivery->project_id, $delivery->channel),
                    'completed' => $this->notifications->notifyCompleted($delivery->order_id, $delivery->project_id, $delivery->channel),
                    default => throw new \RuntimeException('Unknown notification event'),
                };
            });
            DB::table(self::TABLE)->where('id', $id)->where('lease', $lease)->update([
                'delivered_at' => now(), 'lease' => null, 'leased_until' => null, 'last_error' => null, 'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            DB::table(self::TABLE)->where('id', $id)->where('lease', $lease)->update([
                'lease' => null, 'leased_until' => null,
                'available_at' => now()->addSeconds(min(3600, 60 * (2 ** min((int) $delivery->attempts, 6)))),
                'last_error' => mb_substr($exception->getMessage(), 0, 1000), 'updated_at' => now(),
            ]);
            Log::error('[OrderOutbox] delivery failed', ['delivery_id' => $id, 'attempt' => $delivery->attempts + 1]);
        } finally {
            AdminContext::setCurrentProject($previous);
        }
    }

    private function eventName(object $event): ?string
    {
        return match (true) {
            $event instanceof OrderConfirmed => 'confirmed',
            $event instanceof OrderReady => 'ready',
            $event instanceof OrderShipped => 'shipped',
            $event instanceof OrderCompleted => 'completed',
            default => null,
        };
    }
}
