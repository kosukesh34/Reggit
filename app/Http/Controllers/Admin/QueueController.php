<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\QueueStatusRequest;
use App\UseCases\Admin\Queue\AdminQueueIndexUseCase;
use App\UseCases\Admin\Queue\AdminQueueUpdateStatusUseCase;
use App\Util\AdminStatusLabels;

class QueueController extends AdminBaseController
{
    private AdminQueueIndexUseCase $indexUseCase;

    private AdminQueueUpdateStatusUseCase $updateStatusUseCase;

    public function __construct(
        AdminQueueIndexUseCase $indexUseCase,
        AdminQueueUpdateStatusUseCase $updateStatusUseCase
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->updateStatusUseCase = $updateStatusUseCase;
    }

    public function queue()
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->indexUseCase->handle($projectId);

        return view('admin.queue.index', [
            'entries' => $output->entries,
            'waitingCount' => $output->waitingCount,
            'estimatedWaitMinutes' => $output->estimatedWaitMinutes,
            'waitingQueueNumbers' => $output->waitingQueueNumbers,
        ]);
    }

    public function updateQueueStatus(QueueStatusRequest $request, string $projectKey, int $id)
    {
        $status = $request->validated('status');
        $this->updateStatusUseCase->handle($id, $status);

        return redirect()->back()
            ->with('success', 'ステータスを「'.AdminStatusLabels::queueStatusLabel($status).'」に更新しました');
    }
}
