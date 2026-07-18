<?php

namespace App\Services\Admin;

use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\QueueStatus;
use App\Models\Project;
use App\Models\QueueCallLog;
use App\Models\QueueEntry;
use App\Services\Line\LineService;
use App\Services\PushNotificationService;
use App\Support\QueueLiffUrlBuilder;
use App\Util\LogHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class QueueAdminService
{
    private LineService $lineService;

    private PushNotificationService $pushNotificationService;

    private QueueEntryRepository $queueEntryRepository;

    private ShopSettingRepository $shopSettingRepository;

    public function __construct(
        LineService $lineService,
        PushNotificationService $pushNotificationService,
        QueueEntryRepository $queueEntryRepository,
        ShopSettingRepository $shopSettingRepository,
    ) {
        $this->lineService = $lineService;
        $this->pushNotificationService = $pushNotificationService;
        $this->queueEntryRepository = $queueEntryRepository;
        $this->shopSettingRepository = $shopSettingRepository;
    }

    public function getPaginatedList(int $projectId, int $perPage = 30): LengthAwarePaginator
    {
        return QueueEntry::with('member.user', 'user')
            ->where('project_id', $projectId)
            ->visibleOnQueueBoard()
            ->orderByDesc('entered_at')
            ->paginate($perPage);
    }

    public function getWaitingCount(int $projectId): int
    {
        return QueueEntry::where('project_id', $projectId)
            ->where('created_at', '>=', now()->subDay())
            ->where('status', QueueStatus::WAITING)
            ->count();
    }

    public function getEstimatedWaitMinutes(int $projectId): int
    {
        $waiting = $this->getWaitingCount($projectId);
        $avgMinutes = $this->shopSettingRepository->getQueueAvgServeMinutes($projectId);

        return $waiting * $avgMinutes;
    }

    public function getWaitingQueueNumbers(int $projectId): Collection
    {
        return QueueEntry::where('project_id', $projectId)
            ->where('created_at', '>=', now()->subDay())
            ->where('status', QueueStatus::WAITING)
            ->orderBy('queue_number')
            ->pluck('queue_number')
            ->values();
    }

    public function updateStatus(int $id, string $status): void
    {
        $entry = QueueEntry::with('member.user', 'user')->whereKey($id)->first();
        if ($entry === null) {
            return;
        }

        $aggregate = $this->queueEntryRepository->findById((int) $entry->id, (int) $entry->project_id);
        if ($aggregate === null) {
            return;
        }

        $aggregate->applyAdminStatus($status, now());
        $this->queueEntryRepository->save($aggregate);

        if ($status === QueueStatus::CALLED) {
            $this->notifyCalled((int) $entry->id);
        }
    }

    public function notifyCalled(int $id): void
    {
        $entry = QueueEntry::with('member.user', 'user')->whereKey($id)->first();
        if ($entry === null) {
            return;
        }

        $lineUserId = $entry->user?->line_user_id ?? $entry->member?->user?->line_user_id;
        $notifyUserId = (int) ($entry->user_id ?? ($entry->member?->user_id ?? 0));
        $callLog = QueueCallLog::updateOrCreate(
            ['queue_entry_id' => $entry->id],
            [
                'project_id' => (int) $entry->project_id,
                'queue_number' => $entry->queue_number,
                'line_user_id' => $lineUserId,
                'called_at' => now(),
                'push_status' => 'pending',
                'error_message' => null,
            ]
        );
        if ($notifyUserId > 0) {
            $queueNumber = $entry->formattedQueueNumber();
            $pushResult = $this->pushNotificationService->sendQueueCalledToUser((int) $entry->project_id, $notifyUserId, $queueNumber);
            LogHelper::info('[Queue] native push result', [
                'queue_id' => $entry->id,
                'project_id' => (int) $entry->project_id,
                'user_id' => $notifyUserId,
                'target_tokens' => $pushResult['target_tokens'],
                'sent' => $pushResult['sent'],
                'failed' => $pushResult['failed'],
                'skipped' => $pushResult['skipped'],
            ]);
        }

        if ($lineUserId === null || $lineUserId === '' || ! $this->isValidLineUserId($lineUserId)) {
            $callLog->update([
                'push_status' => 'skipped',
                'error_message' => 'invalid line user id',
            ]);
            LogHelper::warning('[Queue] line push skipped: invalid line user id', [
                'queue_id' => $entry->id,
                'line_user_id' => $lineUserId,
            ]);

            return;
        }
        if (app()->environment('testing')) {
            $callLog->update([
                'push_status' => 'skipped',
                'error_message' => 'testing environment',
            ]);

            return;
        }
        $message = sprintf('順番が近づきました。受付番号: %s', $entry->formattedQueueNumber());
        try {
            $project = Project::find((int) $entry->project_id);
            $projectKey = trim((string) ($project?->project_key ?? 'default'));
            if ($projectKey === '') {
                $projectKey = 'default';
            }
            $liffUri = QueueLiffUrlBuilder::build(
                (int) $entry->project_id,
                $projectKey,
                (string) ($entry->qr_token ?? ''),
                (int) $entry->id
            );
            $flex = [
                'type' => 'flex',
                'altText' => $message,
                'contents' => [
                    'type' => 'bubble',
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'spacing' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => '順番のお知らせ', 'weight' => 'bold', 'size' => 'lg', 'color' => '#06C755'],
                            ['type' => 'text', 'text' => $message, 'wrap' => true, 'size' => 'sm'],
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
                                'uri' => $liffUri,
                            ],
                            'color' => '#06C755',
                        ]],
                    ],
                ],
            ];
            LogHelper::info('[Queue] call notification liff url built', [
                'queue_id' => $entry->id,
                'project_key' => $projectKey,
                'is_liff_host' => str_contains($liffUri, 'liff.line.me'),
            ]);
            $this->lineService->pushMessage($lineUserId, $flex);
            $callLog->update([
                'push_status' => 'sent',
                'error_message' => null,
                'line_user_id' => $lineUserId,
            ]);
        } catch (\Exception $e) {
            $callLog->update([
                'push_status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 255),
                'line_user_id' => $lineUserId,
            ]);
            LogHelper::error('[Queue] line push failed', [
                'queue_id' => $entry->id,
                'line_user_id' => $lineUserId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isValidLineUserId(string $lineUserId): bool
    {
        return preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
    }
}
