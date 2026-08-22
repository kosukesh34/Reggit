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
    /** OneSignal API: max subscription_ids per request */
    private const ONESIGNAL_CHUNK_SIZE = 2000;

    /** collapse_id の最大文字数（FCM apns-collapse-id / OneSignal 共通） */
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

        if ($this->isFcmEnabled()) {
            return $this->sendAnnouncementViaFcm($announcement, $tokens);
        }

        if ($this->isOneSignalEnabled()) {
            $result = $this->sendAnnouncementViaOneSignal($announcement, $tokens);
            $this->persistAnnouncementNotificationIds($announcement, $result['notification_ids']);

            return $result;
        }

        LogHelper::warning('[Push] No push provider configured (FCM/OneSignal)', [
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

        $title = trim((string) $announcement->title);
        $data = [
            'type' => self::DATA_TYPE_ANNOUNCEMENT_CANCELLED,
            'announcement_id' => (string) $announcement->id,
            'project_id' => (string) $announcement->project_id,
            'original_title' => (string) $announcement->title,
        ];
        $collapseId = $this->buildAnnouncementCollapseId((int) $announcement->id);

        if ($this->isFcmEnabled()) {
            $this->sendViaFcm(
                $tokens,
                $title !== '' ? $title : 'お知らせ',
                'このお知らせは取り消されました',
                $data,
                $collapseId,
                true,
                true,
                (int) $announcement->project_id
            );

            return;
        }

        if (! $this->isOneSignalEnabled()) {
            return;
        }

        foreach ($this->storedOneSignalNotificationIds($announcement) as $notificationId) {
            $id = trim((string) $notificationId);
            if ($id === '') {
                continue;
            }
            $this->cancelOneSignalNotification($id, (int) $announcement->id, (int) $announcement->project_id);
        }

        $this->sendOneSignalPush(
            $tokens,
            $title !== '' ? $title : 'お知らせ',
            'このお知らせは取り消されました',
            $data,
            $collapseId,
            true,
            true
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

        $resolvedCollapseId = $collapseId ?? $this->resolveCollapseIdFromData($projectId, $userId, $data);

        if ($this->isFcmEnabled()) {
            return $this->sendViaFcm($tokens, $title, $body, $data, $resolvedCollapseId, false, false, $projectId);
        }

        if ($this->isOneSignalEnabled()) {
            $result = $this->sendOneSignalPush(
                $tokens,
                $title,
                $body,
                $data,
                $resolvedCollapseId
            );

            return [
                'target_tokens' => $result['target_tokens'],
                'sent' => $result['sent'],
                'failed' => $result['failed'],
                'skipped' => $result['skipped'],
            ];
        }

        return [
            'target_tokens' => count($tokens),
            'sent' => 0,
            'failed' => 0,
            'skipped' => count($tokens),
        ];
    }

    /**
     * subscription_id リストへ直接 push（キッチン端末など user 非紐付け向け）
     *
     * @param  list<string>  $subscriptionIds
     * @param  array<string, string>  $data
     * @return array{target_tokens:int,sent:int,failed:int,skipped:int,notification_ids:list<string>}
     */
    public function sendToSubscriptionIds(
        array $subscriptionIds,
        string $title,
        string $body,
        array $data = [],
        ?string $collapseId = null,
        bool $useKitchenProvider = false
    ): array {
        if ($subscriptionIds === []) {
            return ['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'notification_ids' => []];
        }

        if ($useKitchenProvider && $this->isKitchenOneSignalEnabled()) {
            return $this->sendOneSignalPush(
                $subscriptionIds,
                $title,
                $body,
                $data,
                $collapseId,
                false,
                false,
                'kitchen_onesignal'
            );
        }

        if ($this->isFcmEnabled()) {
            $result = $this->sendViaFcm($subscriptionIds, $title, $body, $data, $collapseId);

            return [
                'target_tokens' => $result['target_tokens'],
                'sent' => $result['sent'],
                'failed' => $result['failed'],
                'skipped' => $result['skipped'],
                'notification_ids' => [],
            ];
        }

        if ($this->isOneSignalEnabled()) {
            return $this->sendOneSignalPush(
                $subscriptionIds,
                $title,
                $body,
                $data,
                $collapseId
            );
        }

        LogHelper::info('[Push] sendToSubscriptionIds skipped (provider not configured)', [
            'target_tokens' => count($subscriptionIds),
            'use_kitchen' => $useKitchenProvider,
        ]);

        return [
            'target_tokens' => count($subscriptionIds),
            'sent' => 0,
            'failed' => 0,
            'skipped' => count($subscriptionIds),
            'notification_ids' => [],
        ];
    }

    /** @return array{target_tokens:int,sent:int,failed:int,skipped:int} */
    public function sendQueueCalledToUser(int $projectId, int $userId, string $queueNumber): array
    {
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

    /**
     * OneSignal でお知らせ配信（subscription_id は user_devices.push_token に保存されている想定）
     *
     * @param  list<string>  $subscriptionIds
     * @return array{target_tokens:int,sent:int,failed:int,skipped:int,notification_ids:list<string>}
     */
    private function sendAnnouncementViaOneSignal(Announcement $announcement, array $subscriptionIds): array
    {
        return $this->sendOneSignalPush(
            $subscriptionIds,
            (string) $announcement->title,
            (string) ($announcement->body ?? ''),
            [
                'announcement_id' => (string) $announcement->id,
                'project_id' => (string) $announcement->project_id,
                'delivery_type' => (string) $announcement->delivery_type,
            ],
            $this->buildAnnouncementCollapseId((int) $announcement->id)
        );
    }

    /**
     * @param  list<string>  $subscriptionIds
     * @param  array<string,string>  $data
     * @return array{target_tokens:int,sent:int,failed:int,skipped:int,notification_ids:list<string>}
     */
    private function sendOneSignalPush(
        array $subscriptionIds,
        string $title,
        string $body,
        array $data,
        ?string $collapseId = null,
        bool $contentAvailable = false,
        bool $isAnnouncementCancel = false,
        string $configKey = 'onesignal'
    ): array {
        $config = config('push.'.$configKey);
        $appId = trim((string) ($config['app_id'] ?? ''));
        $apiKey = trim((string) ($config['api_key'] ?? ''));
        $apiUrl = rtrim((string) ($config['api_url'] ?? 'https://api.onesignal.com/notifications'), '/');

        if ($appId === '' || $apiKey === '') {
            return [
                'target_tokens' => count($subscriptionIds),
                'sent' => 0,
                'failed' => 0,
                'skipped' => count($subscriptionIds),
                'notification_ids' => [],
            ];
        }

        $totalSent = 0;
        $totalFailed = 0;
        $notificationIds = [];

        $normalizedCollapseId = $this->normalizeCollapseId($collapseId);

        foreach (array_chunk($subscriptionIds, self::ONESIGNAL_CHUNK_SIZE) as $chunk) {
            try {
                $payload = [
                    'app_id' => $appId,
                    'include_subscription_ids' => $chunk,
                    'target_channel' => 'push',
                    'data' => $data,
                    'priority' => 10,
                ];
                if ($isAnnouncementCancel) {
                    $payload['headings'] = ['en' => $title];
                    $payload['contents'] = ['en' => $body];
                    $payload['content_available'] = true;
                    $payload['mutable_content'] = true;
                } elseif ($contentAvailable) {
                    $payload['content_available'] = true;
                } else {
                    $payload['headings'] = ['en' => $title];
                    $payload['contents'] = ['en' => $body];
                }
                if ($normalizedCollapseId !== null) {
                    $payload['collapse_id'] = $normalizedCollapseId;
                    $payload['android_group'] = $normalizedCollapseId;
                    $payload['thread_id'] = $normalizedCollapseId;
                }

                $response = $this->http->post($apiUrl, [
                    'headers' => [
                        'Authorization' => 'Key '.$apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                ]);
            } catch (\Throwable $e) {
                LogHelper::error('[Push] OneSignal request failed', [
                    'error' => $e->getMessage(),
                    'chunk_size' => count($chunk),
                    'title' => $title,
                ]);
                $totalFailed += count($chunk);

                continue;
            }

            $responsePayload = json_decode((string) $response->getBody(), true);
            if (! is_array($responsePayload)) {
                $responsePayload = [];
            }

            $notificationId = trim((string) ($responsePayload['id'] ?? ''));
            if ($notificationId !== '') {
                $notificationIds[] = $notificationId;
            }

            // OneSignal は recipients を省略することがある。非空 id なら作成成功とみなす。
            $chunkSent = 0;
            if (array_key_exists('recipients', $responsePayload) && is_numeric($responsePayload['recipients'])) {
                $chunkSent = max(0, (int) $responsePayload['recipients']);
            } elseif ($notificationId !== '') {
                $chunkSent = count($chunk);
            }

            $totalSent += $chunkSent;
            $totalFailed += max(0, count($chunk) - $chunkSent);

            $errors = $responsePayload['errors'] ?? null;
            if ($notificationId === '' || $errors !== null) {
                LogHelper::warning('[Push] OneSignal response incomplete', [
                    'notification_id' => $notificationId !== '' ? $notificationId : null,
                    'chunk_size' => count($chunk),
                    'recipients' => $responsePayload['recipients'] ?? null,
                    'errors' => $errors,
                    'title' => $title,
                ]);
            }

            if ($isAnnouncementCancel) {
                LogHelper::info('[Push] announcement cancel push sent', [
                    'notification_id' => $notificationId,
                    'chunk_size' => count($chunk),
                    'recipients' => $chunkSent,
                    'errors' => $errors,
                ]);
            }
        }

        return [
            'target_tokens' => count($subscriptionIds),
            'sent' => $totalSent,
            'failed' => $totalFailed,
            'skipped' => 0,
            'notification_ids' => $notificationIds,
        ];
    }

    private function cancelOneSignalNotification(string $notificationId, int $announcementId, int $projectId): void
    {
        $appId = trim((string) config('push.onesignal.app_id'));
        $apiKey = trim((string) config('push.onesignal.api_key'));
        $apiUrl = rtrim((string) config('push.onesignal.api_url'), '/');

        if ($appId === '' || $apiKey === '') {
            return;
        }

        try {
            $this->http->delete($apiUrl.'/'.$notificationId.'?app_id='.urlencode($appId), [
                'headers' => [
                    'Authorization' => 'Key '.$apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (\Throwable $e) {
            LogHelper::warning('[Push] OneSignal cancel failed', [
                'announcement_id' => $announcementId,
                'project_id' => $projectId,
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** @param  list<string>  $notificationIds */
    private function persistAnnouncementNotificationIds(Announcement $announcement, array $notificationIds): void
    {
        if ($notificationIds === []) {
            return;
        }

        $merged = array_values(array_unique(array_filter(
            array_merge($this->storedOneSignalNotificationIds($announcement), $notificationIds),
            fn ($id) => trim((string) $id) !== ''
        )));

        $announcement->onesignal_notification_ids = $merged;
        $announcement->save();
    }

    /** @return list<string> */
    private function storedOneSignalNotificationIds(Announcement $announcement): array
    {
        return is_array($announcement->onesignal_notification_ids)
            ? $announcement->onesignal_notification_ids
            : [];
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

    private function isOneSignalEnabled(): bool
    {
        $config = config('push.onesignal');

        return (bool) ($config['enabled'] ?? false)
            && trim((string) ($config['app_id'] ?? '')) !== ''
            && trim((string) ($config['api_key'] ?? '')) !== '';
    }

    private function isKitchenOneSignalEnabled(): bool
    {
        $config = config('push.kitchen_onesignal');

        return (bool) ($config['enabled'] ?? false)
            && trim((string) ($config['app_id'] ?? '')) !== ''
            && trim((string) ($config['api_key'] ?? '')) !== '';
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
     * @return array{target_tokens:int,sent:int,failed:int,skipped:int}
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
        ];
    }

    private function isFcmEnabled(): bool
    {
        return (new FcmHttpV1Client($this->http))->isConfigured();
    }
}
