<?php

namespace App\Http\Controllers;

use App\Http\Services\LineService;
use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LineWebhookController extends Controller
{
    private $lineService;

    public function __construct(LineService $lineService)
    {
        $this->lineService = $lineService;
    }

    public function handle(Request $request)
    {
        // GETリクエストの場合は、LINE Developers Consoleの検証用に200 OKを返す
        if ($request->isMethod('GET')) {
            return response()->json([
                'message' => 'LINE Webhook endpoint is active. Use POST method to send webhook events.',
                'status' => 'ok'
            ], 200);
        }

        // POSTリクエストの処理
        $signature = $request->header('X-Line-Signature');
        $body = $request->getContent();

        if (!$this->lineService->verifySignature($body, $signature)) {
            Log::warning('Invalid LINE signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $events = json_decode($body, true)['events'] ?? [];

        foreach ($events as $event) {
            $this->handleEvent($event);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleEvent($event)
    {
        $type = $event['type'] ?? null;
        $source = $event['source'] ?? [];
        $userId = $source['userId'] ?? null;

        if (!$userId) {
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

    private function handleFollow($event, $userId)
    {
        $user = User::firstOrCreate(
            ['line_user_id' => $userId],
            ['name' => 'LINE User']
        );

        $profile = $this->lineService->getProfile($userId);
        if ($profile) {
            $user->update([
                'name' => $profile['displayName'] ?? $user->name,
                'profile_image_url' => $profile['pictureUrl'] ?? null,
            ]);
        }

        $message = $this->lineService->createTextMessage(
            "ようこそ！モバイルオーダーアプリへ\n\n" .
            "以下のメニューからお選びください：\n" .
            "・メニューを見る\n" .
            "・会員証を見る\n" .
            "・注文履歴"
        );

        $this->lineService->replyMessage($event['replyToken'] ?? '', $message);
    }

    private function handleMessage($event, $userId)
    {
        $message = $event['message'] ?? [];
        $text = $message['text'] ?? '';

        $user = User::where('line_user_id', $userId)->first();
        if (!$user) {
            return;
        }

        $replyToken = $event['replyToken'] ?? '';

        switch (true) {
            case str_contains($text, 'メニュー') || str_contains($text, 'menu'):
                $this->sendMenu($replyToken, $userId);
                break;
            case str_contains($text, '会員証') || str_contains($text, 'member'):
                $this->sendMemberCard($replyToken, $userId);
                break;
            case str_contains($text, '注文') || str_contains($text, 'order'):
                $this->sendOrderHistory($replyToken, $userId);
                break;
            default:
                $this->sendDefaultMessage($replyToken);
        }
    }

    private function handlePostback($event, $userId)
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

    private function sendMenu($replyToken, $userId)
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
                    'aspectMode' => 'cover'
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => 'モバイルオーダー',
                            'weight' => 'bold',
                            'size' => 'xl'
                        ],
                        [
                            'type' => 'text',
                            'text' => 'LIFFアプリで商品を選択してください',
                            'wrap' => true,
                            'margin' => 'md'
                        ]
                    ]
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
                                'uri' => $liffUrl
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->lineService->replyMessage($replyToken, $message);
    }

    private function sendMemberCard($replyToken, $userId)
    {
        $user = User::where('line_user_id', $userId)->first();
        $member = $user->member ?? null;

        if (!$member) {
            $liffUrl = env('LINE_LIFF_URL', url('/liff'));
            $message = $this->lineService->createTextMessage(
                "会員登録がまだです。\n" .
                "LIFFアプリで会員登録を行ってください。\n\n" .
                $liffUrl
            );
        } else {
            $message = $this->lineService->createTextMessage(
                "会員証情報\n\n" .
                "会員番号: {$member->member_number}\n" .
                "ポイント: {$member->points}pt\n" .
                "ステータス: " . ($member->status === 'active' ? '有効' : '無効')
            );
        }

        $this->lineService->replyMessage($replyToken, $message);
    }

    private function sendOrderHistory($replyToken, $userId)
    {
        $user = User::where('line_user_id', $userId)->first();
        $orders = $user->orders()->latest()->take(5)->get();

        if ($orders->isEmpty()) {
            $message = $this->lineService->createTextMessage("注文履歴はありません。");
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

    private function sendDefaultMessage($replyToken)
    {
        $message = $this->lineService->createTextMessage(
            "以下のコマンドが利用できます：\n" .
            "・メニュー - 商品メニューを見る\n" .
            "・会員証 - 会員証情報を見る\n" .
            "・注文 - 注文履歴を見る"
        );

        $this->lineService->replyMessage($replyToken, $message);
    }
}

