<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry;
use App\Util\AdminContext;

class QueueCancelUseCase
{
    public function handle(int $id): ?QueueEntry
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $entry = QueueEntry::where('project_id', $projectId)->whereKey($id)->first();
        if ($entry === null) {
            return null;
        }
        if ($this->canCancel($entry->status) === false) {
            return $entry;
        }
        $entry->status = QueueStatus::CANCELLED;
        $entry->save();

        return $entry;
    }

    private function canCancel(string $status): bool
    {
        return in_array($status, [QueueStatus::WAITING, QueueStatus::CALLED], true);
    }
}
