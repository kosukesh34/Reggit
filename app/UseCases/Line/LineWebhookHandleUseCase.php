<?php

namespace App\UseCases\Line;

use App\Enums\MemberStatus;
use App\Models\User;
use App\Services\Line\LineService;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class LineWebhookHandleUseCase
{
    public function __construct(
        private LineService $lineService
    ) {}

    public function handle(Request $request): array
    {
        if ($request->isMethod('GET')) {
            return [
                'status' => 200,
                'payload' => [
                    'message' => 'LINE Webhook endpoint is active. Use POST method to send webhook events.',
                    'status' => 'ok',
                ],
            ];
        }

        $signature = $request->header('X-Line-Signature');
        $body = $request->getContent();

        if ($this->lineService->verifySignature($body, $signature) === false) {
            LogHelper::warning('[LINE] invalid signature');

            return [
                'status' => 401,
                'payload' => ['error' => 'Invalid signature'],
            ];
        }

        $events = json_decode($body, true)['events'] ?? [];
        foreach ($events as $event) {
            $this->handleEvent($event);
        }

        return [
            'status' => 200,
            'payload' => ['status' => 'ok'],
        ];
    }

    private function handleEvent(array $event): void
    {
        $type = $event['type'] ?? null;
        $source = $event['source'] ?? [];
        $userId = $source['userId'] ?? null;

        if ($userId === null || $userId === '') {
            return;
        }

        switch ($type) {
            case 'follow':
                $this->handleFollow($event, $userId);
                break;
            case 'message':
                $this->handleMessage($event, $userId);
                break;
            case 'postback':
                $this->handlePostback($event, $userId);
                break;
        }
    }

    private function handleFollow(array $event, string $userId): void
    {
        $user = User::firstOrCreate(
            ['line_user_id' => $userId],
            ['name' => 'LINE User']
        );

        $profile = $this->lineService->getProfile($userId);
        if ($profile !== null && $profile !== false) {
            $user->update([
                'name' => $profile['displayName'] ?? $user->name,
                'profile_image_url' => $profile['pictureUrl'] ?? null,
            ]);
        }

        $message = $this->lineService->createTextMessage(
            "ようこそ！モバイルオーダーアプリへ\n\n".
            "以下のメニューからお選びください：\n".
            "・メニューを見る\n".
            "・会員証を見る\n".
            '・注文履歴'
        );

        $this->lineService->replyMessage($event['replyToken'] ?? '', $message);
    }

    private function handleMessage(array $event, string $userId): void
    {
        $message = $event['message'] ?? [];
        $text = $message['text'] ?? '';

        $user = User::where('line_user_id', $userId)->first();
        if ($user === null) {
            return;
        }

        $replyToken = $event['replyToken'] ?? '';

        if (str_contains($text, 'メニュー') || str_contains($text, 'menu')) {
            $this->sendMenu($replyToken, $userId);

            return;
        }
        if (str_contains($text, '会員証') || str_contains($text, 'member')) {
            $this->sendMemberCard($replyToken, $userId);

            return;
        }
        if (str_contains($text, '注文') || str_contains($text, 'order')) {
            $this->sendOrderHistory($replyToken, $userId);

            return;
        }
        $this->sendDefaultMessage($replyToken);
    }

    private function handlePostback(array $event, string $userId): void
    {
        $data = json_decode($event['postback']['data'] ?? '{}', true);
        $action = $data['action'] ?? '';
        $replyToken = $event['replyToken'] ?? '';

        switch ($action) {
            case 'view_menu':
                $this->sendMenu($replyToken, $userId);
                break;
            case 'view_member':
                $this->sendMemberCard($replyToken, $userId);
                break;
            case 'view_orders':
                $this->sendOrderHistory($replyToken, $userId);
                break;
        }
    }

    private function sendMenu(string $replyToken, string $userId): void
    {
        $liffUrl = env('LINE_LIFF_URL', url('/liff'));
        $message = [
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
                            'text' => 'モバイルオーダー',
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

        $this->lineService->replyMessage($replyToken, $message);
    }

    private function sendMemberCard(string $replyToken, string $userId): void
    {
        $user = User::where('line_user_id', $userId)->first();
        $member = $user?->member;

        if ($member === null) {
            $liffUrl = env('LINE_LIFF_URL', url('/liff'));
            $message = $this->lineService->createTextMessage(
                "会員登録がまだです。\n".
                "LIFFアプリで会員登録を行ってください。\n\n".
                $liffUrl
            );
        } else {
            $message = $this->lineService->createTextMessage(
                "会員証情報\n\n".
                "会員番号: {$member->member_number}\n".
                "ポイント: {$member->getDisplayPoints()}pt\n".
                'ステータス: '.($member->status === MemberStatus::ACTIVE ? '有効' : '無効')
            );
        }

        $this->lineService->replyMessage($replyToken, $message);
    }

    private function sendOrderHistory(string $replyToken, string $userId): void
    {
        $user = User::where('line_user_id', $userId)->first();
        if ($user === null) {
            return;
        }
        $orders = $user->orders()->latest()->take(5)->get();

        if ($orders->isEmpty()) {
            $message = $this->lineService->createTextMessage('注文履歴はありません。');
        } else {
            $text = "最近の注文履歴:\n\n";
            foreach ($orders as $order) {
                $text .= "{$order->order_number}\n";
                $text .= "金額: ¥{$order->total_amount}\n";
                $text .= "ステータス: {$order->status}\n\n";
            }
            $message = $this->lineService->createTextMessage($text);
        }

        $this->lineService->replyMessage($replyToken, $message);
    }

    private function sendDefaultMessage(string $replyToken): void
    {
        $message = $this->lineService->createTextMessage(
            "以下のコマンドが利用できます：\n".
            "・メニュー - 商品メニューを見る\n".
            "・会員証 - 会員証情報を見る\n".
            '・注文 - 注文履歴を見る'
        );

        $this->lineService->replyMessage($replyToken, $message);
    }
}
