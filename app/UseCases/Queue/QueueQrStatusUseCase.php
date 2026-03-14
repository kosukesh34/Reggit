<?php

namespace App\UseCases\Queue;

use App\Services\Line\LineService;
use App\Models\Project;
use App\Models\QueueEntry as QueueEntryModel;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\UserService;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\Util\AdminContext;
use App\Util\LogHelper;
use App\Util\StrHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QueueQrStatusUseCase
{
    private const SESSION_QUEUE_ENTRY_KEY_PREFIX = 'mobile_order_queue_entry';

    public function __construct(
        private UserService $userService,
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
        private LineService $lineService
    ) {}

    public function handle(Request $request, string $token): ?QueueEntry
    {
        $currentProjectId = AdminContext::getCurrentProjectId() ?? 1;

        $entry = QueueEntryModel::with('user')
            ->where('project_id', $currentProjectId)
            ->where('qr_token', $token)
            ->first();
        if ($entry === null && ctype_digit($token)) {
            $entry = QueueEntryModel::with('user')
                ->where('project_id', $currentProjectId)
                ->whereKey((int) $token)
                ->first();
            if ($entry !== null && StrHelper::isEmptyStringOrNull($entry->qr_token)) {
                $entry->qr_token = $this->generateQrToken();
                $entry->save();
                $entry->refresh();
            }
        }
        if ($entry === null) {
            return null;
        }

        $lineUserId = $this->resolveLineUserId($request);
        if (! $this->isValidLineUserId($lineUserId)) {
            LogHelper::warning('[Queue] qr link rejected: missing line user id', [
                'queue_id' => $entry->id,
                'project_id' => $currentProjectId,
                'has_header' => ! StrHelper::isEmptyStringOrNull((string) $request->header('X-Line-User-Id')),
                'has_query_line_user_id' => ! StrHelper::isEmptyStringOrNull((string) $request->query('line_user_id')),
                'user_agent' => (string) $request->userAgent(),
            ]);
            throw new \RuntimeException('LINEログインが必要です');
        }

        $user = $this->resolveUserForLine($request, $lineUserId, $currentProjectId);
        $projectId = $entry->project_id;
        $linkedNow = false;

        // 既に同一ユーザーで紐づいている場合は何もせず通知も送らない
        if ($entry->user_id !== null && (int) $entry->user_id === (int) $user->id) {
            $stats = $this->statsService->getStats($projectId, $entry->queue_number);
            $this->rememberEntryInSession($request, $projectId, (int) $entry->id);

            return $this->entryFactory->create($entry->refresh(), $stats);
        }

        if ($entry->user_id !== null && $entry->user_id !== $user->id) {
            $entryLineUserId = (string) ($entry->user?->line_user_id ?? '');
            if ($entryLineUserId !== '' && hash_equals($entryLineUserId, (string) $lineUserId)) {
                // 既存顧客で user レコードが重複しているケースは同一 LINE とみなして再紐付け（通知は送らない）
                $entry->user_id = $user->id;
                $entry->save();
                $entry->refresh();
                // 既に紐づき済みのため linkedNow は false のまま（重複通知を防ぐ）
            } else {
                throw new \RuntimeException('別のLINEアカウントで登録済みです');
            }
        }

        if ($entry->user_id === null) {
            $entry->user_id = $user->id;
            if ($user->member !== null) {
                $entry->member_id = $user->member->id;
            }
            $entry->save();
            $entry->refresh();
            $linkedNow = true;
        }

        if ($linkedNow) {
            $this->sendLinkedCompleteMessage($entry, (string) $lineUserId);
        }

        $stats = $this->statsService->getStats($projectId, $entry->queue_number);
        $this->rememberEntryInSession($request, $projectId, (int) $entry->id);

        return $this->entryFactory->create($entry, $stats);
    }

    private function generateQrToken(): string
    {
        $maxAttempts = 5;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $token = Str::random(32);
            if (! QueueEntryModel::where('qr_token', $token)->exists()) {
                return $token;
            }
        }

        return (string) Str::uuid();
    }

    private function sendLinkedCompleteMessage(QueueEntryModel $entry, string $lineUserId): void
    {
        try {
            $project = Project::find($entry->project_id);
            $projectName = $project?->name ?? '店舗';
            $queueNo = (string) ($entry->queue_number ?? $entry->id);
            $projectKey = trim((string) ($project?->project_key ?? 'default'));
            if ($projectKey === '') {
                $projectKey = 'default';
            }
            $liffUrl = ShopSetting::getLineLiffUrl((int) $entry->project_id);
            if (StrHelper::isEmptyStringOrNull($liffUrl)) {
                $liffUrl = url('/liff/'.$projectKey);
            }
            $uri = $this->buildQueueLiffUri(
                $liffUrl,
                $projectKey,
                (string) ($entry->qr_token ?? $entry->id)
            );

            $flex = [
                'type' => 'flex',
                'altText' => '順番待ちのLINE連携が完了しました',
                'contents' => [
                    'type' => 'bubble',
                    'header' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            ['type' => 'text', 'text' => '連携完了', 'weight' => 'bold', 'size' => 'lg', 'color' => '#06C755'],
                            ['type' => 'text', 'text' => $projectName, 'size' => 'sm', 'color' => '#666666', 'margin' => 'sm'],
                        ],
                    ],
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'spacing' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '順番待ちの通知連携が完了しました。', 'wrap' => true, 'size' => 'sm'],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'contents' => [
                                    ['type' => 'text', 'text' => '受付番号', 'size' => 'sm', 'color' => '#777777', 'flex' => 2],
                                    ['type' => 'text', 'text' => 'No.'.$queueNo, 'size' => 'md', 'weight' => 'bold', 'flex' => 3],
                                ],
                            ],
                            ['type' => 'text', 'text' => '店舗が呼び出すと、このLINEに通知が届きます。', 'wrap' => true, 'size' => 'xs', 'color' => '#666666'],
                        ],
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
                                'label' => '待ち状況を確認',
                                'uri' => $uri,
                            ],
                            'color' => '#06C755',
                        ]],
                    ],
                ],
            ];

            $this->lineService->pushMessage($lineUserId, $flex);
        } catch (\Throwable $e) {
            LogHelper::warning('[Queue] linked completion message failed', [
                'queue_id' => $entry->id,
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function rememberEntryInSession(Request $request, int $projectId, int $entryId): void
    {
        if (! $request->hasSession()) {
            return;
        }
        $request->session()->put($this->getSessionEntryKey($projectId), $entryId);
    }

    private function getSessionEntryKey(int $projectId): string
    {
        return self::SESSION_QUEUE_ENTRY_KEY_PREFIX.'_'.$projectId;
    }

    private function resolveLineUserId(Request $request): string
    {
        $header = trim((string) ($request->header('X-Line-User-Id') ?? ''));
        if ($header !== '') {
            return $header;
        }

        $query = trim((string) ($request->query('line_user_id') ?? ''));
        if ($query !== '') {
            return $query;
        }

        $user = $this->userService->findUser($request);
        if ($user !== null && ! StrHelper::isEmptyStringOrNull($user->line_user_id)) {
            return (string) $user->line_user_id;
        }

        return '';
    }

    private function resolveUserForLine(Request $request, string $lineUserId, int $projectId): User
    {
        $user = $this->userService->findUser($request);
        if (
            $user !== null
            && (int) $user->project_id === $projectId
            && hash_equals((string) $user->line_user_id, $lineUserId)
        ) {
            return $user;
        }

        $user = User::where('project_id', $projectId)
            ->where('line_user_id', $lineUserId)
            ->first();
        if ($user !== null) {
            return $user;
        }

        return User::create([
            'project_id' => $projectId,
            'line_user_id' => $lineUserId,
            'name' => 'LINE User',
        ]);
    }

    private function isValidLineUserId(string $lineUserId): bool
    {
        return preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
    }

    private function buildQueueLiffUri(string $liffUrl, string $projectKey, string $queueToken): string
    {
        $parsedForHost = parse_url($liffUrl);
        $host = is_array($parsedForHost) && isset($parsedForHost['host'])
            ? strtolower((string) $parsedForHost['host'])
            : '';
        if ($host === 'miniapp.line.me' || $host === 'liff.line.me') {
            $liffUrl = url('/liff/'.$projectKey);
        }

        $parts = parse_url($liffUrl);
        if ($parts === false) {
            return $liffUrl.(str_contains($liffUrl, '?') ? '&' : '?')
                .'project_key='.urlencode($projectKey)
                .'&queue_token='.urlencode($queueToken);
        }

        $queryParams = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $queryParams);
        }

        unset($queryParams['project_key'], $queryParams['queue_token'], $queryParams['queue_id']);
        $orderedParams = array_merge(
            ['project_key' => $projectKey, 'queue_token' => $queueToken],
            $queryParams
        );
        $query = http_build_query($orderedParams);

        $uri = '';
        if (isset($parts['scheme'])) {
            $uri .= $parts['scheme'].'://';
        }
        if (isset($parts['user'])) {
            $uri .= $parts['user'];
            if (isset($parts['pass'])) {
                $uri .= ':'.$parts['pass'];
            }
            $uri .= '@';
        }
        if (isset($parts['host'])) {
            $uri .= $parts['host'];
        }
        if (isset($parts['port'])) {
            $uri .= ':'.$parts['port'];
        }
        $uri .= $parts['path'] ?? '';
        if ($query !== '') {
            $uri .= '?'.$query;
        }
        if (isset($parts['fragment'])) {
            $uri .= '#'.$parts['fragment'];
        }

        return $uri;
    }
}
