<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\QueueStatusRequest;
use App\Services\Admin\QueueAdminService;
use App\Util\AdminStatusLabels;

class QueueController extends AdminBaseController
{
    public function __construct(
        private QueueAdminService $queueAdminService
    ) {
    }

    public function queue()
    {
        $projectId = $this->resolveAdminProjectId();
        $entries = $this->queueAdminService->getPaginatedList($projectId, 50);
        $waitingCount = $this->queueAdminService->getWaitingCount($projectId);
        $estimatedWaitMinutes = $this->queueAdminService->getEstimatedWaitMinutes($projectId);
        $waitingQueueNumbers = $this->queueAdminService->getWaitingQueueNumbers($projectId);
        return view('admin.queue.index', compact('entries', 'waitingCount', 'estimatedWaitMinutes', 'waitingQueueNumbers'));
    }

    public function updateQueueStatus(QueueStatusRequest $request, string $projectKey, int $id)
    {
        $status = $request->validated('status');
        $this->queueAdminService->updateStatus($id, $status);
        return redirect()->back()
            ->with('success', 'ステータスを「' . AdminStatusLabels::queueStatusLabel($status) . '」に更新しました');
    }
}
