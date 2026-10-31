<?php

namespace App\Services;

use App\Domain\Settings\Repositories\OrderNotificationSettings;
use App\Enums\OrderType;
use App\Mail\ShopOrderMail;
use App\Models\Order;
use App\Models\Project;
use App\Services\Line\LineService;
use App\Services\Shop\ShopOperations;
use App\Support\LiffTabUrlBuilder;
use App\Util\LogHelper;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class OrderNotificationService
{
    public function __construct(
        private LineService $lineService,
        private OrderNotificationSettings $shopSettingRepository,
        private PushNotificationService $pushNotificationService,
        private ShopOperations $shopOperations,
        private LiffTabUrlBuilder $liffTabUrls,
    ) {}

    public function notifyConfirmed(int $orderId, int $projectId, string $channel = 'all'): void
    {
        if (Order::query()->whereKey($orderId)->where('project_id', $projectId)->value('order_type') === OrderType::SHIPPING) {
            // 配送注文は受け取りに来ないため、店舗設定に関係なく受付を知らせる
            $this->notifyMobileOrder(
                $orderId,
                $projectId,
                true,
                'ご注文を受け付けました',
                'order_confirmed',
                static fn (string $refNumber): string => ($refNumber !== '' ? '注文番号: '.$refNumber."\n" : '').'発送の準備ができ次第お知らせします。',
                $channel,
                OrderType::SHIPPING,
            );

            return;
        }
        $this->notifyMobileOrder(
            $orderId,
            $projectId,
            $this->shopSettingRepository->isOrderLineNotifyEnabled($projectId),
            '注文が完了しました',
            'order_confirmed',
            static function (string $refNumber): string {
                return $refNumber !== ''
                    ? '引換番号: '.$refNumber."\n店舗でこの番号をお伝えください。"
                    : 'ご注文ありがとうございました。';
            },
            $channel
        );
    }

    /** 配送注文の購入者へメールで知らせる。メールアドレス未確認の購入者には送らない。 */
    public function emailShopOrder(int $orderId, int $projectId, string $event): void
    {
        $order = Order::query()->with(['items', 'user'])->whereKey($orderId)->where('project_id', $projectId)->first();
        $email = trim((string) ($order?->user?->email ?? ''));
        if ($order === null || $order->order_type !== OrderType::SHIPPING || $email === '' || $order->user?->email_verified_at === null) {
            return;
        }
        $project = Project::findOrFail($projectId);
        Mail::to($email)->send(new ShopOrderMail(
            $order,
            $event,
            (string) $project->name,
            $this->shopOperations->legalNotice($projectId),
            URL::signedRoute('shop.receipt', ['project_key' => $project->project_key, 'order' => $order->id]),
            ShopOperations::trackingUrl($order->shipping_carrier, $order->shipping_tracking_number),
        ));
    }

    public function notifyReady(int $orderId, int $projectId, string $channel = 'all'): void
    {
        $this->notifyMobileOrder(
            $orderId,
            $projectId,
            $this->shopSettingRepository->isOrderReadyLineNotifyEnabled($projectId),
            '受け取りの準備ができました',
            'order_ready',
            static function (string $refNumber): string {
                return $refNumber !== ''
                    ? '引換番号: '.$refNumber."\n店舗でお受け取りください。"
                    : '店舗でお受け取りください。';
            },
            $channel
        );
    }

    public function notifyShipped(int $orderId, int $projectId, string $channel = 'all'): void
    {
        // 配送注文の発送連絡は店舗設定に関係なく送る（受け取りに来ないため唯一の連絡になる）
        $this->notifyMobileOrder(
            $orderId,
            $projectId,
            true,
            'ご注文の商品を発送しました',
            'order_shipped',
            static fn (string $refNumber): string => $refNumber !== ''
                ? '注文番号: '.$refNumber."\n到着まで今しばらくお待ちください。"
                : '到着まで今しばらくお待ちください。',
            $channel,
            OrderType::SHIPPING,
        );
    }

    public function notifyCompleted(int $orderId, int $projectId, string $channel = 'all'): void
    {
        $this->notifyMobileOrder(
            $orderId,
            $projectId,
            false,
            'ご注文の商品をお渡ししました',
            'order_completed',
            static fn (string $refNumber): string => 'ご利用ありがとうございました。',
            $channel,
        );
    }

    /**
     * @param  callable(string): string  $bodyBuilder
     */
    private function notifyMobileOrder(
        int $orderId,
        int $projectId,
        bool $isEnabled,
        string $title,
        string $pushType,
        callable $bodyBuilder,
        string $channel,
        string $orderType = OrderType::MOBILE,
    ): void {
        $order = Order::query()
            ->with('user')
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->first();
        if ($order === null) {
            return;
        }
        if ($order->order_type !== $orderType) {
            return;
        }
        $refNumber = $order->formattedOrderNumber();
        $body = $bodyBuilder($refNumber);
        if ($channel === 'all' || $channel === 'native') {
            $this->sendNativePush($order, $projectId, $title, $body, $pushType);
        }

        if ($isEnabled === false || $channel === 'native') {
            return;
        }

        $projectKey = Project::query()->where('id', $projectId)->value('project_key');
        $projectKey = is_string($projectKey) ? trim($projectKey) : '';
        if ($projectKey === '') {
            LogHelper::error('[Order] LINE notify skipped (project_key missing)', [
                'order_id' => $orderId,
                'project_id' => $projectId,
            ]);

            return;
        }

        $this->sendLinePush($order, $projectId, $projectKey, $title, $body);
    }

    private function sendNativePush(
        Order $order,
        int $projectId,
        string $title,
        string $body,
        string $pushType,
    ): void {
        $userId = (int) ($order->user_id ?? 0);
        if ($userId <= 0) {
            return;
        }

        $result = $this->pushNotificationService->sendToUser(
            $projectId,
            $userId,
            $title,
            $body,
            [
                'type' => $pushType,
                'tab' => 'orders',
                'order_id' => (string) $order->id,
                'project_id' => (string) $projectId,
            ]
        );

        if ((int) ($result['failed'] ?? 0) > 0) {
            throw new \RuntimeException('Order push delivery failed');
        }

        LogHelper::info('[Order] native push result', [
            'order_id' => $order->id,
            'project_id' => $projectId,
            'type' => $pushType,
            'target_tokens' => (int) ($result['target_tokens'] ?? 0),
            'sent' => (int) ($result['sent'] ?? 0),
            'failed' => (int) ($result['failed'] ?? 0),
            'skipped' => (int) ($result['skipped'] ?? 0),
        ]);
    }

    private function sendLinePush(
        Order $order,
        int $projectId,
        string $projectKey,
        string $title,
        string $body,
    ): void {
        $user = $order->user;
        if ($user === null) {
            return;
        }
        $lineUserId = trim((string) ($user->line_user_id ?? ''));
        if ($lineUserId === '' || $this->isValidLineUserId($lineUserId) === false) {
            return;
        }
        if ($this->shopSettingRepository->hasLineChannelAccessToken($projectId) === false) {
            LogHelper::info('[Order] LINE notify skipped (token not configured)', [
                'order_id' => $order->id,
                'project_id' => $projectId,
            ]);

            return;
        }

        $refNumber = $order->formattedOrderNumber();

        try {
            $ordersUrl = $this->liffTabUrls->build($projectId, $projectKey, 'orders');
            $flex = [
                'type' => 'flex',
                'altText' => $title.($refNumber !== '' ? '（'.($order->order_type === OrderType::SHIPPING ? '注文番号' : '引換番号').': '.$refNumber.'）' : ''),
                'contents' => [
                    'type' => 'bubble',
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'spacing' => 'md',
                        'contents' => array_values(array_filter([
                            ['type' => 'text', 'text' => $title, 'weight' => 'bold', 'size' => 'lg', 'color' => '#06C755'],
                            ['type' => 'text', 'text' => $body, 'wrap' => true, 'size' => 'sm'],
                            $refNumber !== ''
                                ? ['type' => 'text', 'text' => $refNumber, 'wrap' => true, 'size' => 'xxl', 'weight' => 'bold', 'align' => 'center']
                                : null,
                        ])),
                    ],
                    'footer' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [[
                            'type' => 'button',
                            'style' => 'primary',
                            'height' => 'sm',
                            'action' => [
                                'type' => 'uri',
                                'label' => '注文履歴を見る',
                                'uri' => $ordersUrl,
                            ],
                            'color' => '#06C755',
                        ]],
                    ],
                ],
            ];
            $this->lineService->pushMessage(
                $lineUserId,
                $flex,
                $this->shopSettingRepository->getLineChannelAccessToken($projectId)
            );
        } catch (\Throwable $e) {
            LogHelper::error('[Order] LINE notify failed', [
                'order_id' => $order->id,
                'project_id' => $projectId,
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function isValidLineUserId(string $lineUserId): bool
    {
        return preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
    }
}
