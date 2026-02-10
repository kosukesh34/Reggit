<?php

namespace App\Services\Admin;

use App\Models\QueueEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
        return QueueEntry::where('status', 'waiting')->count();
    }

    public function getEstimatedWaitMinutes(int $projectId): int
    {
        $waiting = $this->getWaitingCount();
        return $waiting * 10;
    }

    public function getWaitingQueueNumbers(): array
    {
        return QueueEntry::where('status', 'waiting')
            ->orderBy('queue_number')
            ->pluck('queue_number')
            ->all();
    }

    public function updateStatus(int $id, string $status): void
    {
        $payload = ['status' => $status];
        if ($status === 'called') {
            $payload['called_at'] = now();
        }
        QueueEntry::whereKey($id)->update($payload);
    }
}
