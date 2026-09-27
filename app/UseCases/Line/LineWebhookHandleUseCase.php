<?php

namespace App\UseCases\Line;

use App\Domain\Settings\Repositories\LineConnectionSettings;
use App\Enums\MemberStatus;
use App\Models\Project;
use App\Models\User;
use App\Services\Line\LineService;
use App\Services\Line\LineWebhookProjectResolver;
use App\UseCases\Line\Data\LineWebhookInput;
use App\Util\AdminContext;
use App\Util\LogHelper;

class LineWebhookHandleUseCase
{
    private ?int $projectId = null;

    private ?string $channelAccessToken = null;

    public function __construct(
        private LineService $lineService,
        private LineWebhookProjectResolver $projectResolver,
        private LineConnectionSettings $shopSettingRepository,
    ) {}

    public function handle(LineWebhookInput $input): array
    {
        if ($input->isGet) {
            return [
                'status' => 200,
                'payload' => [
                    'message' => 'LINE Webhook endpoint is active. Use POST method to send webhook events.',
                    'status' => 'ok',
                ],
            ];
        }

        $signature = $input->signature;
        $body = $input->body;
        $projectId = $this->projectResolver->resolveProjectId($body, $signature);
        if ($projectId === null) {
            LogHelper::warning('[LINE] invalid signature');

            return [
                'status' => 401,
                'payload' => ['error' => 'Invalid signature'],
            ];
        }

        $project = Project::query()->find($projectId);
        if ($project !== null) {
            AdminContext::setCurrentProject($project);
        }
        $this->projectId = $projectId;
        $token = trim($this->shopSettingRepository->getLineChannelAccessToken($projectId));
        $this->channelAccessToken = $token !== '' ? $token : null;

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
        $projectId = $this->projectId;
        if ($projectId === null) {
            LogHelper::warning('[LINE] follow skipped: no active project');

            return;
        }

        $user = User::firstOrCreate(
            [
                'project_id' => $projectId,
                'line_user_id' => $userId,
            ],
        );

        $profile = $this->lineService->getProfile($userId, $this->channelAccessToken);
        if ($profile !== null && $profile !== false) {
            $displayName = trim((string) ($profile['displayName'] ?? ''));
            $updates = [
                'profile_image_url' => $profile['pictureUrl'] ?? null,
            ];
            if ($displayName !== '') {
                $updates['line_name'] = $displayName;
            }
            $user->update($updates);
        }

        $message = $this->lineService->createTextMessage(
            'ようこそ！'.config('app.name')."へ\n\n".
            "以下のメニューからお選びください：\n".
            "・メニューを見る\n".
            "・会員証を見る\n".
            '・注文履歴'
        );

        $this->reply($event['replyToken'] ?? '', $message);
    }

    private function handleMessage(array $event, string $userId): void
    {
        $message = $event['message'] ?? [];
        $text = $message['text'] ?? '';

        $user = $this->findUserByLineId($userId);
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
        $liffUrl = $this->resolveLiffUrl();
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

        $this->reply($replyToken, $message);
    }

    private function sendMemberCard(string $replyToken, string $userId): void
    {
        $user = $this->findUserByLineId($userId);
        $member = $user?->member;

        if ($member === null) {
            $liffUrl = $this->resolveLiffUrl();
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

        $this->reply($replyToken, $message);
    }

    private function sendOrderHistory(string $replyToken, string $userId): void
    {
        $user = $this->findUserByLineId($userId);
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

        $this->reply($replyToken, $message);
    }

    private function findUserByLineId(string $userId): ?User
    {
        if ($this->projectId === null) {
            return null;
        }

        return User::query()
            ->where('project_id', $this->projectId)
            ->where('line_user_id', $userId)
            ->first();
    }

    private function resolveLiffUrl(): string
    {
        if ($this->projectId !== null) {
            $projectUrl = trim($this->shopSettingRepository->getLineLiffUrl($this->projectId));
            if ($projectUrl !== '') {
                return $projectUrl;
            }
        }
        $fallback = trim((string) config('line.liff_url', ''));
        if ($fallback !== '') {
            return $fallback;
        }

        return url('/liff');
    }

    /** @param array<string, mixed> $message */
    private function reply(string $replyToken, array $message): void
    {
        $this->lineService->replyMessage($replyToken, $message, $this->channelAccessToken);
    }

    private function sendDefaultMessage(string $replyToken): void
    {
        $message = $this->lineService->createTextMessage(
            "以下のコマンドが利用できます：\n".
            "・メニュー - 商品メニューを見る\n".
            "・会員証 - 会員証情報を見る\n".
            '・注文 - 注文履歴を見る'
        );

        $this->reply($replyToken, $message);
    }
}
