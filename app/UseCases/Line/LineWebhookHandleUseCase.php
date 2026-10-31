<?php

namespace App\UseCases\Line;

use App\Domain\Settings\Repositories\LineConnectionSettings;
use App\Models\LineFollowEvent;
use App\Models\Project;
use App\Models\User;
use App\Services\Line\LineReplyMessages;
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
        private LineReplyMessages $messages,
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
            case 'unfollow':
                $this->handleUnfollow($event, $userId);
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
        if ($user->line_blocked_at !== null) {
            $user->update(['line_blocked_at' => null]);
        }
        $this->recordFollowEvent($event, LineFollowEvent::TYPE_FOLLOW, (int) $user->id);

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

        $this->reply($event['replyToken'] ?? '', $this->messages->welcome());
    }

    /** LINE ではブロックが unfollow として届く。送信は200で成功扱いになるため、ここで記録する */
    private function handleUnfollow(array $event, string $userId): void
    {
        if ($this->projectId === null) {
            return;
        }
        $user = $this->findUserByLineId($userId);
        $user?->update(['line_blocked_at' => now()]);
        $this->recordFollowEvent($event, LineFollowEvent::TYPE_UNFOLLOW, $user?->id);
    }

    /** 再送された同じイベントは webhookEventId の一意制約で無視する */
    private function recordFollowEvent(array $event, string $type, ?int $userId): void
    {
        $eventId = $event['webhookEventId'] ?? null;
        LineFollowEvent::query()->insertOrIgnore([
            'project_id' => $this->projectId,
            'user_id' => $userId,
            'type' => $type,
            'webhook_event_id' => is_string($eventId) && $eventId !== '' ? mb_substr($eventId, 0, 64) : null,
            'created_at' => now(),
        ]);
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
        $this->reply($replyToken, $this->messages->menu($this->resolveLiffUrl()));
    }

    private function sendMemberCard(string $replyToken, string $userId): void
    {
        $user = $this->findUserByLineId($userId);
        $member = $user?->member;

        $this->reply($replyToken, $this->messages->memberCard($member, $this->resolveLiffUrl()));
    }

    private function sendOrderHistory(string $replyToken, string $userId): void
    {
        $user = $this->findUserByLineId($userId);
        if ($user === null) {
            return;
        }
        $orders = $user->orders()->latest()->take(5)->get();

        $this->reply($replyToken, $this->messages->orderHistory($orders));
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
        $this->reply($replyToken, $this->messages->help());
    }
}
