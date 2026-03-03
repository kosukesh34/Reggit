<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Member;
use App\Models\UserDevice;
use App\Util\LogHelper;
use GuzzleHttp\Client;

class PushNotificationService
{
    private const INVALID_TOKEN_ERRORS = ['NotRegistered', 'InvalidRegistration'];

    /** OneSignal API: max subscription_ids per request */
    private const ONESIGNAL_CHUNK_SIZE = 2000;

    private Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 15]);
    }

    /** @return array{target_tokens:int,sent:int,failed:int,skipped:int} */
    public function sendAnnouncement(Announcement $announcement): array
    {
        $tokens = $this->resolveTargetTokens($announcement);
        if ($tokens === []) {
            return ['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        }

        if ($this->isOneSignalEnabled()) {
            return $this->sendAnnouncementViaOneSignal($announcement, $tokens);
        }

        if (! $this->isFcmEnabled()) {
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
            ];
        }

        $result = ['target_tokens' => count($tokens), 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach (array_chunk($tokens, 500) as $chunk) {
            $chunkResult = $this->sendFcmChunk($announcement, $chunk);
            $result['sent'] += $chunkResult['sent'];
            $result['failed'] += $chunkResult['failed'];
        }

        return $result;
    }

    /**
     * OneSignal でお知らせ配信（subscription_id は user_devices.push_token に保存されている想定）
     *
     * @param list<string> $subscriptionIds
     * @return array{target_tokens:int,sent:int,failed:int,skipped:int}
     */
    private function sendAnnouncementViaOneSignal(Announcement $announcement, array $subscriptionIds): array
    {
        $appId = trim((string) config('push.onesignal.app_id'));
        $apiKey = trim((string) config('push.onesignal.api_key'));
        $apiUrl = rtrim((string) config('push.onesignal.api_url'), '/');

        if ($appId === '' || $apiKey === '') {
            LogHelper::warning('[Push] OneSignal skipped (app_id or api_key missing)', [
                'announcement_id' => $announcement->id,
                'project_id' => $announcement->project_id,
                'target_tokens' => count($subscriptionIds),
            ]);

            return [
                'target_tokens' => count($subscriptionIds),
                'sent' => 0,
                'failed' => 0,
                'skipped' => count($subscriptionIds),
            ];
        }

        $totalSent = 0;
        $totalFailed = 0;

        foreach (array_chunk($subscriptionIds, self::ONESIGNAL_CHUNK_SIZE) as $chunk) {
            try {
                $response = $this->http->post($apiUrl, [
                    'headers' => [
                        'Authorization' => 'Key '.$apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'app_id' => $appId,
                        'include_subscription_ids' => $chunk,
                        'target_channel' => 'push',
                        'headings' => ['en' => (string) $announcement->title],
                        'contents' => ['en' => (string) ($announcement->body ?? '')],
                        'data' => [
                            'announcement_id' => (string) $announcement->id,
                            'project_id' => (string) $announcement->project_id,
                            'delivery_type' => (string) $announcement->delivery_type,
                        ],
                    ],
                ]);
            } catch (\Throwable $e) {
                LogHelper::error('[Push] OneSignal request failed', [
                    'announcement_id' => $announcement->id,
                    'project_id' => $announcement->project_id,
                    'error' => $e->getMessage(),
                    'chunk_size' => count($chunk),
                ]);
                $totalFailed += count($chunk);

                continue;
            }

            $payload = json_decode((string) $response->getBody(), true);
            $recipients = $payload['recipients'] ?? 0;
            $totalSent += (int) $recipients;
            $totalFailed += count($chunk) - (int) $recipients;
        }

        if ($totalFailed > 0) {
            LogHelper::warning('[Push] OneSignal partial failure', [
                'announcement_id' => $announcement->id,
                'project_id' => $announcement->project_id,
                'sent' => $totalSent,
                'failed' => $totalFailed,
            ]);
        }

        return [
            'target_tokens' => count($subscriptionIds),
            'sent' => $totalSent,
            'failed' => $totalFailed,
            'skipped' => 0,
        ];
    }

    /** @return list<string> */
    private function resolveTargetTokens(Announcement $announcement): array
    {
        $query = UserDevice::query()
            ->where('project_id', (int) $announcement->project_id)
            ->whereNotNull('push_token')
            ->where('push_token', '!=', '');

        $targetMemberIds = is_array($announcement->target_member_ids)
            ? array_values(array_filter(array_map('intval', $announcement->target_member_ids), fn ($id) => $id > 0))
            : [];
        if ($announcement->delivery_type === Announcement::DELIVERY_TYPE_PERSONAL && $targetMemberIds !== []) {
            $userIds = Member::where('project_id', (int) $announcement->project_id)
                ->whereIn('id', $targetMemberIds)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            if ($userIds === []) {
                return [];
            }
            $query->whereIn('user_id', $userIds);
        }

        return $query->pluck('push_token')
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => $token !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function isOneSignalEnabled(): bool
    {
        $config = config('push.onesignal');

        return (bool) ($config['enabled'] ?? false)
            && trim((string) ($config['app_id'] ?? '')) !== ''
            && trim((string) ($config['api_key'] ?? '')) !== '';
    }

    /** @param list<string> $tokens */
    private function sendFcmChunk(Announcement $announcement, array $tokens): array
    {
        try {
            $response = $this->http->post((string) config('push.fcm.endpoint'), [
                'headers' => [
                    'Authorization' => 'key='.(string) config('push.fcm.server_key'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'registration_ids' => $tokens,
                    'priority' => 'high',
                    'notification' => [
                        'title' => (string) $announcement->title,
                        'body' => (string) ($announcement->body ?? ''),
                    ],
                    'data' => [
                        'announcement_id' => (string) $announcement->id,
                        'project_id' => (string) $announcement->project_id,
                        'delivery_type' => (string) $announcement->delivery_type,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            LogHelper::error('[Push] FCM request failed', [
                'announcement_id' => $announcement->id,
                'project_id' => $announcement->project_id,
                'error' => $e->getMessage(),
                'target_tokens' => count($tokens),
            ]);

            return ['sent' => 0, 'failed' => count($tokens)];
        }

        $payload = json_decode((string) $response->getBody(), true);
        $results = is_array($payload['results'] ?? null) ? $payload['results'] : [];

        $sent = 0;
        $failed = 0;
        $invalidTokens = [];
        foreach ($tokens as $index => $token) {
            $item = $results[$index] ?? null;
            $error = is_array($item) ? ($item['error'] ?? null) : null;
            if ($error === null) {
                $sent++;

                continue;
            }
            $failed++;
            if (in_array((string) $error, self::INVALID_TOKEN_ERRORS, true)) {
                $invalidTokens[] = $token;
            }
        }

        if ($invalidTokens !== []) {
            UserDevice::where('project_id', (int) $announcement->project_id)
                ->whereIn('push_token', $invalidTokens)
                ->update(['push_token' => null]);
        }

        if ($failed > 0) {
            LogHelper::warning('[Push] FCM partial failure', [
                'announcement_id' => $announcement->id,
                'project_id' => $announcement->project_id,
                'sent' => $sent,
                'failed' => $failed,
            ]);
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    private function isFcmEnabled(): bool
    {
        return (bool) config('push.fcm.enabled') && trim((string) config('push.fcm.server_key')) !== '';
    }
}
