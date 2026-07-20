<?php

namespace App\UseCases\Queue;

use App\Models\Project;
use App\Models\QueueEntry as QueueEntryModel;
use App\Models\User;
use App\Services\DeviceService;
use App\Services\Line\LineService;
use App\Services\PushNotificationService;
use App\Support\MemberChannel;
use App\Support\QueueLiffUrlBuilder;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\UseCases\Queue\Data\QueueQrStatusInput;
use App\UseCases\Queue\Data\QueueQrStatusOutput;
use App\Util\LogHelper;
use App\Util\StrHelper;
use Illuminate\Support\Str;

class QueueQrStatusUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
        private LineService $lineService,
        private PushNotificationService $pushNotificationService,
        private DeviceService $deviceService,
    ) {}

    public function handle(QueueQrStatusInput $input): ?QueueQrStatusOutput
    {
        $entry = QueueEntryModel::with('user')
            ->where('project_id', $input->projectId)
            ->where('qr_token', $input->token)
            ->first();
        if ($entry === null && ctype_digit($input->token)) {
            $entry = QueueEntryModel::with('user')
                ->where('project_id', $input->projectId)
                ->whereKey((int) $input->token)
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

        $hasLine = MemberChannel::isValidLineUserId($input->lineUserId);
        $user = $hasLine
            ? $this->resolveUserForLine($input)
            : $this->resolveUserForNative($input);

        $projectId = $entry->project_id;
        $linkedNow = false;

        if ($entry->user_id !== null && (int) $entry->user_id === (int) $user->id) {
            $stats = $this->statsService->getStats($projectId, $entry->queue_number);

            return new QueueQrStatusOutput(
                $this->entryFactory->create($entry->refresh(), $stats),
                (int) $entry->id,
                (int) $entry->project_id,
            );
        }

        if ($entry->user_id !== null && (int) $entry->user_id !== (int) $user->id) {
            if ($hasLine) {
                $entryLineUserId = (string) ($entry->user?->line_user_id ?? '');
                if ($entryLineUserId !== '' && hash_equals($entryLineUserId, $input->lineUserId)) {
                    $entry->user_id = $user->id;
                    $entry->save();
                    $entry->refresh();
                } else {
                    throw new \RuntimeException('別のLINEアカウントで登録済みです');
                }
            } else {
                throw new \RuntimeException('別のアカウントで登録済みです');
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

        if ($linkedNow && $hasLine) {
            $this->sendLinkedCompleteMessage($entry, $input->lineUserId);
        }
        if ($linkedNow && ! $hasLine) {
            $this->ensureNativeDeviceLinked($input, $user);
            $this->sendLinkedCompleteNativePush($entry, (int) $user->id);
        }

        $stats = $this->statsService->getStats($projectId, $entry->queue_number);

        return new QueueQrStatusOutput(
            $this->entryFactory->create($entry, $stats),
            (int) $entry->id,
            (int) $entry->project_id,
        );
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
            $queueNo = $entry->formattedQueueNumber();
            $projectKey = trim((string) ($project?->project_key ?? ''));
            if ($projectKey === '') {
                LogHelper::error('[Queue] linked completion skipped (project_key missing)', [
                    'queue_id' => $entry->id,
                    'project_id' => $entry->project_id,
                ]);

                return;
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
                'altText' => '順番待ちのアプリ連携が完了しました',
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
                            ['type' => 'text', 'text' => '順番待ちのアプリ連携が完了しました。', 'wrap' => true, 'size' => 'sm'],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'contents' => [
                                    ['type' => 'text', 'text' => '受付番号', 'size' => 'sm', 'color' => '#777777', 'flex' => 2],
                                    ['type' => 'text', 'text' => 'No.'.$queueNo, 'size' => 'md', 'weight' => 'bold', 'flex' => 3],
                                ],
                            ],
                            ['type' => 'text', 'text' => '店舗が呼び出すと、通知が届きます。', 'wrap' => true, 'size' => 'xs', 'color' => '#666666'],
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

    private function ensureNativeDeviceLinked(QueueQrStatusInput $input, User $user): void
    {
        $deviceIdentifier = trim((string) ($input->deviceIdentifier ?? ''));
        if ($deviceIdentifier === '') {
            return;
        }

        $this->deviceService->register($user, [
            'device_identifier' => $deviceIdentifier,
            'platform' => trim((string) ($input->devicePlatform ?? 'unknown')) ?: 'unknown',
            'os_version' => $input->deviceOsVersion,
            'app_version' => $input->deviceAppVersion,
        ]);
    }

    private function sendLinkedCompleteNativePush(QueueEntryModel $entry, int $userId): void
    {
        try {
            $queueNo = $entry->formattedQueueNumber();
            $result = $this->pushNotificationService->sendToUser(
                (int) $entry->project_id,
                $userId,
                '順番待ちのアプリ連携が完了しました',
                sprintf('受付番号 %s の待ち状況を通知で受け取れます。', $queueNo),
                [
                    'type' => 'queue_linked',
                    'queue_number' => $queueNo,
                    'project_id' => (string) $entry->project_id,
                    'queue_entry_id' => (string) $entry->id,
                ],
                'queue-linked-'.$entry->id
            );
            LogHelper::info('[Queue] linked completion native push result', [
                'queue_id' => $entry->id,
                'project_id' => (int) $entry->project_id,
                'user_id' => $userId,
                'target_tokens' => $result['target_tokens'],
                'sent' => $result['sent'],
                'failed' => $result['failed'],
                'skipped' => $result['skipped'],
            ]);
        } catch (\Throwable $e) {
            LogHelper::warning('[Queue] linked completion native push failed', [
                'queue_id' => $entry->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveUserForLine(QueueQrStatusInput $input): User
    {
        $contextUser = $input->contextUser;
        if (
            $contextUser !== null
            && (int) $contextUser->project_id === $input->projectId
            && hash_equals((string) $contextUser->line_user_id, $input->lineUserId)
        ) {
            return $contextUser;
        }

        $user = User::where('project_id', $input->projectId)
            ->where('line_user_id', $input->lineUserId)
            ->first();
        if ($user !== null) {
            return $user;
        }

        return User::create([
            'project_id' => $input->projectId,
            'line_user_id' => $input->lineUserId,
            'name' => 'LINE User',
        ]);
    }

    private function resolveUserForNative(QueueQrStatusInput $input): User
    {
        $contextUser = $input->contextUser;
        if ($contextUser !== null && (int) $contextUser->project_id === $input->projectId) {
            return $contextUser;
        }

        LogHelper::warning('[Queue] qr link rejected: missing native identity', [
            'project_id' => $input->projectId,
        ]);
        throw new \RuntimeException('LINEログインが必要です');
    }
}
