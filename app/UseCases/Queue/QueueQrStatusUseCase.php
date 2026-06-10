<?php

namespace App\UseCases\Queue;

use App\Models\Project;
use App\Models\QueueEntry as QueueEntryModel;
use App\Models\User;
use App\Services\Line\LineService;
use App\Services\Queue\QueueEntrySessionStore;
use App\Support\QueueLiffUrlBuilder;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\UseCases\Queue\Data\QueueQrStatusInput;
use App\Util\AdminContext;
use App\Util\LogHelper;
use App\Util\StrHelper;
use Illuminate\Support\Str;

class QueueQrStatusUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
        private LineService $lineService,
        private QueueEntrySessionStore $sessionStore,
    ) {}

    public function handle(QueueQrStatusInput $input): ?QueueEntry
    {
        $currentProjectId = AdminContext::getCurrentProjectId() ?? 1;
        $token = $input->token;

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

        $lineUserId = $this->resolveLineUserId($input);
        if (! $this->isValidLineUserId($lineUserId)) {
            LogHelper::warning('[Queue] qr link rejected: missing line user id', [
                'queue_id' => $entry->id,
                'project_id' => $currentProjectId,
                'has_header' => ! StrHelper::isEmptyStringOrNull((string) $input->headerLineUserId),
                'has_query_line_user_id' => ! StrHelper::isEmptyStringOrNull((string) $input->queryLineUserId),
                'user_agent' => $input->userAgent,
            ]);
            throw new \RuntimeException('LINEログインが必要です');
        }

        $user = $this->resolveUserForLine($input, $lineUserId, $currentProjectId);
        $projectId = $entry->project_id;
        $linkedNow = false;

        // 既に同一ユーザーで紐づいている場合は何もせず通知も送らない
        if ($entry->user_id !== null && (int) $entry->user_id === (int) $user->id) {
            $stats = $this->statsService->getStats($projectId, $entry->queue_number);
            $this->sessionStore->putEntryId($projectId, (int) $entry->id);

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
        $this->sessionStore->putEntryId($projectId, (int) $entry->id);

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
            $uri = QueueLiffUrlBuilder::build(
                (int) $entry->project_id,
                $projectKey,
                (string) ($entry->qr_token ?? ''),
                (int) $entry->id
            );
            LogHelper::info('[Queue] linked completion liff url built', [
                'queue_id' => $entry->id,
                'project_key' => $projectKey,
                'is_liff_host' => str_contains($uri, 'liff.line.me'),
            ]);

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

    private function resolveLineUserId(QueueQrStatusInput $input): string
    {
        $header = trim((string) ($input->headerLineUserId ?? ''));
        if ($header !== '') {
            return $header;
        }

        $query = trim((string) ($input->queryLineUserId ?? ''));
        if ($query !== '') {
            return $query;
        }

        if ($input->sessionUser !== null && ! StrHelper::isEmptyStringOrNull($input->sessionUser->line_user_id)) {
            return (string) $input->sessionUser->line_user_id;
        }

        return '';
    }

    private function resolveUserForLine(QueueQrStatusInput $input, string $lineUserId, int $projectId): User
    {
        $user = $input->sessionUser;
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
}
