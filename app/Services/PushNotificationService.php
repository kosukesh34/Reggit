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

    private Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 8]);
    }

    /** @return array{target_tokens:int,sent:int,failed:int,skipped:int} */
    public function sendAnnouncement(Announcement $announcement): array
    {
        $tokens = $this->resolveTargetTokens($announcement);
        if ($tokens === []) {
            return ['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        }

        if (! $this->isFcmEnabled()) {
            LogHelper::warning('[Push] FCM skipped (not configured)', [
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
            $chunkResult = $this->sendChunk($announcement, $chunk);
            $result['sent'] += $chunkResult['sent'];
            $result['failed'] += $chunkResult['failed'];
        }

        return $result;
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

    /** @param list<string> $tokens */
    private function sendChunk(Announcement $announcement, array $tokens): array
    {
        try {
            $response = $this->http->post((string) config('push.fcm.endpoint'), [
                'headers' => [
                    'Authorization' => 'key=' . (string) config('push.fcm.server_key'),
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

