<?php

namespace App\Services;

use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Services\Line\LineService;
use App\Support\LiffTabUrlBuilder;
use App\Util\LogHelper;

class OrderNotificationService
{
    public function __construct(
        private LineService $lineService,
    ) {}

    public function notifyConfirmed(int $orderId, int $projectId): void
    {
        $this->notifyMobileOrder(
            $orderId,
            $projectId,
            ShopSetting::isOrderLineNotifyEnabled($projectId),
            '注文が完了しました',
            static function (string $refNumber): string {
                return $refNumber !== ''
                    ? '引換番号: '.$refNumber."\n店舗でこの番号をお伝えください。"
                    : 'ご注文ありがとうございました。';
            }
        );
    }

    public function notifyReady(int $orderId, int $projectId): void
    {
        $this->notifyMobileOrder(
            $orderId,
            $projectId,
            ShopSetting::isOrderReadyLineNotifyEnabled($projectId),
            '受け取りの準備ができました',
            static function (string $refNumber): string {
                return $refNumber !== ''
                    ? '引換番号: '.$refNumber."\n店舗でお受け取りください。"
                    : '店舗でお受け取りください。';
            }
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
        callable $bodyBuilder,
    ): void {
        $order = Order::query()
            ->with('user')
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->first();
        if ($order === null) {
            return;
        }
        if ($order->order_type !== OrderType::MOBILE) {
            return;
        }
        if ($isEnabled === false) {
            LogHelper::info('[Order] LINE notify skipped (disabled)', [
                'order_id' => $orderId,
                'project_id' => $projectId,
                'title' => $title,
            ]);

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

        $this->sendLinePush($order, $projectId, $projectKey, $title, $bodyBuilder);
    }

    /**
     * @param  callable(string): string  $bodyBuilder
     */
    private function sendLinePush(
        Order $order,
        int $projectId,
        string $projectKey,
        string $title,
        callable $bodyBuilder,
    ): void {
        $user = $order->user;
        if ($user === null) {
            return;
        }
        $lineUserId = trim((string) ($user->line_user_id ?? ''));
        if ($lineUserId === '' || $this->isValidLineUserId($lineUserId) === false) {
            return;
        }
        if (ShopSetting::hasLineChannelAccessToken($projectId) === false) {
            LogHelper::info('[Order] LINE notify skipped (token not configured)', [
                'order_id' => $order->id,
                'project_id' => $projectId,
            ]);

            return;
        }

        $refNumber = $order->formattedOrderNumber();
        $body = $bodyBuilder($refNumber);

        try {
            $ordersUrl = LiffTabUrlBuilder::build($projectId, $projectKey, 'orders');
            $flex = [
                'type' => 'flex',
                'altText' => $title.($refNumber !== '' ? '（引換番号: '.$refNumber.'）' : ''),
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
                ShopSetting::getLineChannelAccessToken($projectId)
            );
        } catch (\Throwable $e) {
            LogHelper::error('[Order] LINE notify failed', [
                'order_id' => $order->id,
                'project_id' => $projectId,
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isValidLineUserId(string $lineUserId): bool
    {
        return preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
    }
}
