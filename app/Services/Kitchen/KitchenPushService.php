<?php

namespace App\Services\Kitchen;

use App\Models\KitchenDevice;
use App\Models\Order;
use App\Services\PushNotificationService;
use App\Util\LogHelper;

class KitchenPushService
{
    public function __construct(
        private PushNotificationService $pushNotificationService,
    ) {}

    public function notifyNewOrder(int $orderId, int $projectId): void
    {
        $order = Order::query()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->first();
        if ($order === null || $order->store_id === null) {
            return;
        }

        $tokens = KitchenDevice::query()
            ->where('project_id', $projectId)
            ->where('store_id', $order->store_id)
            ->whereNotNull('push_token')
            ->where('push_token', '!=', '')
            ->pluck('push_token')
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => $token !== '')
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            LogHelper::info('[Kitchen] push skipped (no devices)', [
                'order_id' => $orderId,
                'project_id' => $projectId,
            ]);

            return;
        }

        $refNumber = $order->formattedOrderNumber();
        $title = '新しい注文';
        $body = $refNumber !== ''
            ? '引換番号 '.$refNumber.' の注文が入っています'
            : '新しい注文が入っています';

        $result = $this->pushNotificationService->sendToSubscriptionIds(
            $tokens,
            $title,
            $body,
            [
                'type' => 'kitchen_order',
                'order_id' => (string) $order->id,
                'project_id' => (string) $projectId,
                'order_number' => $refNumber,
            ],
            'kitchen-order-'.(string) $order->id
        );

        $invalidTokens = $result['invalid_tokens'] ?? [];
        if ($invalidTokens !== []) {
            KitchenDevice::query()
                ->where('project_id', $projectId)
                ->where('store_id', $order->store_id)
                ->whereIn('push_token', $invalidTokens)
                ->update(['push_token' => null]);
        }

        if ((int) ($result['failed'] ?? 0) > 0) {
            throw new \RuntimeException('Kitchen push delivery failed');
        }

        LogHelper::info('[Kitchen] push finished', [
            'order_id' => $orderId,
            'project_id' => $projectId,
            'target_tokens' => $result['target_tokens'] ?? 0,
            'sent' => $result['sent'] ?? 0,
        ]);
    }
}
