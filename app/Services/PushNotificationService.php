<?php

namespace App\Services;

use App\Enums\MemberNotificationPreference;
use App\Models\Announcement;
use App\Models\Member;
use App\Models\UserDevice;
use App\Util\LogHelper;
use GuzzleHttp\Client;

class PushNotificationService
{
    /** collapse_id の最大文字数（FCM apns-collapse-id / android.collapse_key） */
    private const COLLAPSE_ID_MAX_LENGTH = 64;

    public const DATA_TYPE_ANNOUNCEMENT_CANCELLED = 'announcement_cancelled';

    private Client $http;

    public function __construct(Client $http)
    {
        $this->http = $http;
    }

    /** @return array{target_tokens:int,sent:int,failed:int,skipped:int,notification_ids:list<string>} */
    public function sendAnnouncement(Announcement $announcement): array
    {
        $tokens = $this->resolveTargetTokens($announcement);
        if ($tokens === []) {
            return ['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'notification_ids' => []];
        }

        if (! $this->isFcmEnabled()) {
            LogHelper::warning('[Push] FCM not configured', [
                'announcement_id' => $announcement->id,
                'project_id' => $announcement->project_id,
                'target_tokens' => count($tokens),
            ]);

            return [
                'target_tokens' => count($tokens),
                'sent' => 0,
                'failed' => 0,
                'skipped' => count($tokens),
                'notification_ids' => [],
            ];
        }

        return $this->sendAnnouncementViaFcm($announcement, $tokens);
    }

    /** 取消し ID を記憶し、端末へ取消しプッシュ（collapse 上書き）。失敗しても例外は投げない。 */
    public function cancelAnnouncementPush(Announcement $announcement): void
    {
        app(CancelledAnnouncementIdStore::class)->remember(
            (int) $announcement->project_id,
            (int) $announcement->id,
            (string) $announcement->title
        );

        $tokens = $this->resolveTargetTokens($announcement);
        if ($tokens === []) {
            LogHelper::warning('[Push] cancelAnnouncementPush skipped (no target tokens)', [
                'announcement_id' => $announcement->id,
                'project_id' => $announcement->project_id,
            ]);

            return;
        }

        if (! $this->isFcmEnabled()) {
            return;
        }

        $title = trim((string) $announcement->title);
        $this->sendViaFcm(
            $tokens,
            $title !== '' ? $title : 'お知らせ',
            'このお知らせは取り消されました',
            [
                'type' => self::DATA_TYPE_ANNOUNCEMENT_CANCELLED,
                'announcement_id' => (string) $announcement->id,
                'project_id' => (string) $announcement->project_id,
                'original_title' => (string) $announcement->title,
            ],
            $this->buildAnnouncementCollapseId((int) $announcement->id),
            true,
            true,
            (int) $announcement->project_id
        );
    }

    /** @return array{target_tokens:int,sent:int,failed:int,skipped:int} */
    public function sendToUser(
        int $projectId,
        int $userId,
        string $title,
        string $body,
        array $data = [],
        ?string $collapseId = null
    ): array {
        $tokens = UserDevice::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->whereNotNull('push_token')
            ->where('push_token', '!=', '')
            ->pluck('push_token')
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => $token !== '')
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            return ['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        }

        if (! $this->isFcmEnabled()) {
            return [
                'target_tokens' => count($tokens),
                'sent' => 0,
                'failed' => 0,
                'skipped' => count($tokens),
            ];
        }

        $resolvedCollapseId = $collapseId ?? $this->resolveCollapseIdFromData($projectId, $userId, $data);

        return $this->sendViaFcm($tokens, $title, $body, $data, $resolvedCollapseId, false, false, $projectId);
    }

    /**
     * FCM registration token リストへ直接 push（キッチン端末など user 非紐付け向け）
     *
     * @param  list<string>  $tokens
     * @param  array<string, string>  $data
     * @return array{target_tokens:int,sent:int,failed:int,skipped:int,notification_ids:list<string>,invalid_tokens:list<string>}
     */
    public function sendToSubscriptionIds(
        array $tokens,
        string $title,
        string $body,
        array $data = [],
        ?string $collapseId = null,
        bool $useKitchenProvider = false
    ): array {
        unset($useKitchenProvider);

        if ($tokens === []) {
            return [
                'target_tokens' => 0,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'notification_ids' => [],
                'invalid_tokens' => [],
            ];
        }

        if (! $this->isFcmEnabled()) {
            LogHelper::info('[Push] sendToSubscriptionIds skipped (FCM not configured)', [
                'target_tokens' => count($tokens),
            ]);

            return [
                'target_tokens' => count($tokens),
                'sent' => 0,
                'failed' => 0,
                'skipped' => count($tokens),
                'notification_ids' => [],
                'invalid_tokens' => [],
            ];
        }

        $result = $this->sendViaFcm($tokens, $title, $body, $data, $collapseId);

        return [
            'target_tokens' => $result['target_tokens'],
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'skipped' => $result['skipped'],
            'notification_ids' => [],
            'invalid_tokens' => $result['invalid_tokens'],
        ];
    }

    /** @return array{target_tokens:int,sent:int,failed:int,skipped:int} */
    public function sendQueueCalledToUser(int $projectId, int $userId, string $queueNumber): array
    {
        // 呼び出しは販促通知ではなく来店運用に必要な通知のため、会員の通常通知設定では抑止しない。
        $title = '順番待ちのお知らせ';
        $body = sprintf('順番が近づきました。受付番号: %s', $queueNumber);
        $data = [
            'type' => 'queue_called',
            'queue_number' => (string) $queueNumber,
            'project_id' => (string) $projectId,
        ];

        return $this->sendToUser(
            $projectId,
            $userId,
            $title,
            $body,
            $data,
            $this->buildQueueCollapseId($projectId, $userId)
        );
    }

    /** @return list<string> */
    private function resolveTargetTokens(Announcement $announcement): array
    {
        $query = UserDevice::query()
            ->where('project_id', (int) $announcement->project_id)
            ->whereNotNull('push_token')
            ->where('push_token', '!=', '');

        $hasTargetSnapshot = is_array($announcement->target_member_ids);
        $targetMemberIds = $hasTargetSnapshot
            ? array_values(array_filter(array_map('intval', $announcement->target_member_ids), fn ($id) => $id > 0))
            : [];
        $memberQuery = Member::query()
            ->where('project_id', (int) $announcement->project_id)
            ->where(function ($query) use ($announcement): void {
                $query->whereNull('notification_preference')
                    ->orWhereIn(
                        'notification_preference',
                        MemberNotificationPreference::eligibleValues($announcement->delivery_coupon_id !== null),
                    );
            });
        if ($hasTargetSnapshot) {
            if ($targetMemberIds === []) {
                return [];
            }
            $memberQuery->whereIn('id', $targetMemberIds);
        }

        $userIds = $memberQuery->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if ($userIds === []) {
            return [];
        }
        $query->whereIn('user_id', $userIds);

        return $query->pluck('push_token')
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => $token !== '')
            ->unique()
            ->values()
            ->all();
    }

    /** @param  array<string,string>  $data */
    private function resolveCollapseIdFromData(int $projectId, int $userId, array $data): ?string
    {
        return match ((string) ($data['type'] ?? '')) {
            'coupon_issued' => $this->buildCouponCollapseId($projectId, $userId),
            'queue_called' => $this->buildQueueCollapseId($projectId, $userId),
            default => null,
        };
    }

    private function buildQueueCollapseId(int $projectId, int $userId): string
    {
        return $this->normalizeCollapseId(sprintf('reggit:queue:%d:%d', $projectId, $userId)) ?? '';
    }

    private function buildCouponCollapseId(int $projectId, int $userId): string
    {
        return $this->normalizeCollapseId(sprintf('reggit:coupon:%d:%d', $projectId, $userId)) ?? '';
    }

    private function buildAnnouncementCollapseId(int $announcementId): string
    {
        return $this->normalizeCollapseId(sprintf('reggit:announcement:%d', $announcementId)) ?? '';
    }

    private function normalizeCollapseId(?string $collapseId): ?string
    {
        $normalized = trim((string) $collapseId);
        if ($normalized === '') {
            return null;
        }

        if (strlen($normalized) > self::COLLAPSE_ID_MAX_LENGTH) {
            return substr($normalized, 0, self::COLLAPSE_ID_MAX_LENGTH);
        }

        return $normalized;
    }

    /**
     * @param  list<string>  $tokens
     * @return array{target_tokens:int,sent:int,failed:int,skipped:int,notification_ids:list<string>}
     */
    private function sendAnnouncementViaFcm(Announcement $announcement, array $tokens): array
    {
        $result = $this->sendViaFcm(
            $tokens,
            (string) $announcement->title,
            (string) ($announcement->body ?? ''),
            [
                'announcement_id' => (string) $announcement->id,
                'project_id' => (string) $announcement->project_id,
                'delivery_type' => (string) $announcement->delivery_type,
            ],
            $this->buildAnnouncementCollapseId((int) $announcement->id),
            false,
            false,
            (int) $announcement->project_id
        );

        return [
            'target_tokens' => $result['target_tokens'],
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'skipped' => $result['skipped'],
            'notification_ids' => [],
        ];
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<string, string>  $data
     * @return array{target_tokens:int,sent:int,failed:int,skipped:int,invalid_tokens:list<string>}
     */
    private function sendViaFcm(
        array $tokens,
        string $title,
        string $body,
        array $data,
        ?string $collapseId = null,
        bool $contentAvailable = false,
        bool $mutableContent = false,
        ?int $projectId = null
    ): array {
        $client = new FcmHttpV1Client($this->http);
        $result = $client->sendToTokens(
            $tokens,
            $title,
            $body,
            $data,
            $this->normalizeCollapseId($collapseId),
            $contentAvailable,
            $mutableContent
        );

        if ($projectId !== null && $result['invalid_tokens'] !== []) {
            UserDevice::query()
                ->where('project_id', $projectId)
                ->whereIn('push_token', $result['invalid_tokens'])
                ->update(['push_token' => null]);
        }

        return [
            'target_tokens' => count($tokens),
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'skipped' => 0,
            'invalid_tokens' => $result['invalid_tokens'],
        ];
    }

    private function isFcmEnabled(): bool
    {
        return (new FcmHttpV1Client($this->http))->isConfigured();
    }
}
