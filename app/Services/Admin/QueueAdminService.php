<?php

namespace App\Services\Admin;

use App\Models\QueueEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Enums\QueueStatus;
use Illuminate\Support\Collection;
use App\Http\Services\LineService;
use App\Util\LogHelper;

class QueueAdminService
{
    public function getPaginatedList(int $perPage = 50): LengthAwarePaginator
    {
        return QueueEntry::with('member')
            ->orderByDesc('entered_at')
            ->paginate($perPage);
    }

    public function getWaitingCount(): int
    {
        return QueueEntry::where('status', QueueStatus::WAITING)->count();
    }

    public function getEstimatedWaitMinutes(int $projectId): int
    {
        $waiting = $this->getWaitingCount();
        return $waiting * 10;
    }

    public function getWaitingQueueNumbers(): Collection
    {
        return QueueEntry::where('status', QueueStatus::WAITING)
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
            if ($lineUserId !== null && $lineUserId !== '') {
                if (app()->environment('testing')) {
                    return;
                }
                $message = sprintf('順番が近づきました。受付番号: %s', $entry->queue_number ?? $entry->id);
                try {
                    app(LineService::class)->pushMessage($lineUserId, ['type' => 'text', 'text' => $message]);
                } catch (\Exception $e) {
                    LogHelper::error('[Queue] line push failed', [
                        'queue_id' => $entry->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
