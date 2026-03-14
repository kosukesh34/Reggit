<?php

namespace App\Services\Admin;

use App\Enums\QueueStatus;
use App\Models\QueueCallLog;
use App\Models\QueueEntry;
use App\Services\Line\LineService;
use App\Util\LogHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class QueueAdminService
{
    public function __construct(
        private LineService $lineService
    ) {}

    public function getPaginatedList(int $projectId, int $perPage = 50): LengthAwarePaginator
    {
        return QueueEntry::with('member.user', 'user')
            ->where('project_id', $projectId)
            ->orderByDesc('entered_at')
            ->paginate($perPage);
    }

    public function getWaitingCount(int $projectId): int
    {
        return QueueEntry::where('project_id', $projectId)
            ->where('status', QueueStatus::WAITING)
            ->count();
    }

    public function getEstimatedWaitMinutes(int $projectId): int
    {
        $waiting = $this->getWaitingCount($projectId);

        return $waiting * 10;
    }

    public function getWaitingQueueNumbers(int $projectId): Collection
    {
        return QueueEntry::where('project_id', $projectId)
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

        $payload = ['status' => $status];
        if ($status === QueueStatus::CALLED) {
            $payload['called_at'] = $entry->called_at ?? now();
        }
        if ($status === QueueStatus::ENTERED) {
            $payload['entered_at'] = $entry->entered_at ?? now();
        }
        $entry->update($payload);

        if ($status === QueueStatus::CALLED) {
            $lineUserId = $entry->user?->line_user_id ?? $entry->member?->user?->line_user_id;
            $callLog = QueueCallLog::firstOrCreate(
                ['queue_entry_id' => $entry->id],
                [
                    'project_id' => (int) $entry->project_id,
                    'queue_number' => $entry->queue_number,
                    'line_user_id' => $lineUserId,
                    'called_at' => now(),
                    'push_status' => 'pending',
                ]
            );
            if ($callLog->wasRecentlyCreated === false) {
                return;
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
            $message = sprintf('順番が近づきました。受付番号: %s', $entry->queue_number ?? $entry->id);
            try {
                $this->lineService->pushMessage($lineUserId, ['type' => 'text', 'text' => $message]);
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
    }

    private function isValidLineUserId(string $lineUserId): bool
    {
        return preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
    }
}
