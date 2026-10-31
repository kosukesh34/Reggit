<?php

namespace App\Services\Line;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Order;
use App\Services\Member\MemberPointLedger;
use Illuminate\Support\Collection;

/** LINE Webhook への返信メッセージを組み立てる。 */
class LineReplyMessages
{
    public function __construct(
        private LineService $lineService,
        private MemberPointLedger $pointLedger,
    ) {}

    /** @return array<string, mixed> */
    public function welcome(): array
    {
        return $this->lineService->createTextMessage(
            'ようこそ！'.config('app.name')."へ\n\n".
            "以下のメニューからお選びください：\n".
            "・メニューを見る\n".
            "・会員証を見る\n".
            '・注文履歴'
        );
    }

    /** @return array<string, mixed> */
    public function menu(string $liffUrl): array
    {
        return [
            'type' => 'flex',
            'altText' => 'メニューを表示',
            'contents' => [
                'type' => 'bubble',
                'hero' => [
                    'type' => 'image',
                    'url' => 'https://via.placeholder.com/1040x1040/FF6D6D/FFFFFF?text=Menu',
                    'size' => 'full',
                    'aspectRatio' => '1:1',
                    'aspectMode' => 'cover',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => config('app.name'),
                            'weight' => 'bold',
                            'size' => 'xl',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'LIFFアプリで商品を選択してください',
                            'wrap' => true,
                            'margin' => 'md',
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'height' => 'sm',
                            'action' => [
                                'type' => 'uri',
                                'label' => 'メニューを開く',
                                'uri' => $liffUrl,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  string  $liffUrl  未登録のときに案内する登録先
     * @return array<string, mixed>
     */
    public function memberCard(?Member $member, string $liffUrl): array
    {
        if ($member === null) {
            return $this->lineService->createTextMessage(
                "会員登録がまだです。\n".
                "LIFFアプリで会員登録を行ってください。\n\n".
                $liffUrl
            );
        }

        return $this->lineService->createTextMessage(
            "会員証情報\n\n".
            "会員番号: {$member->member_number}\n".
            "ポイント: {$this->pointLedger->displayPoints($member)}pt\n".
            'ステータス: '.($member->status === MemberStatus::ACTIVE ? '有効' : '無効')
        );
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array<string, mixed>
     */
    public function orderHistory(Collection $orders): array
    {
        if ($orders->isEmpty()) {
            return $this->lineService->createTextMessage('注文履歴はありません。');
        }

        $text = "最近の注文履歴:\n\n";
        foreach ($orders as $order) {
            $text .= "{$order->order_number}\n";
            $text .= "金額: ¥{$order->total_amount}\n";
            $text .= "ステータス: {$order->status}\n\n";
        }

        return $this->lineService->createTextMessage($text);
    }

    /** @return array<string, mixed> */
    public function help(): array
    {
        return $this->lineService->createTextMessage(
            "以下のコマンドが利用できます：\n".
            "・メニュー - 商品メニューを見る\n".
            "・会員証 - 会員証情報を見る\n".
            '・注文 - 注文履歴を見る'
        );
    }
}
