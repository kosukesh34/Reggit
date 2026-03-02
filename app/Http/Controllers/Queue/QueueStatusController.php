<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\Models\QueueEntry as QueueEntryModel;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\UseCases\Queue\QueueStatsService;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueStatusController extends Controller
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory
    ) {}

    public function __invoke(Request $request, int $id)
    {
        try {
            $projectId = AdminContext::getCurrentProjectId() ?? 1;
            $entry = QueueEntryModel::where('project_id', $projectId)->whereKey($id)->first();
            if ($entry === null) {
                return response()->json(['error' => '対象が見つかりません。'], 404);
            }

            $stats = $this->statsService->getStats($projectId, $entry->queue_number);
            $dto = $this->entryFactory->create($entry, $stats);

            return response()->json([
                'updated_at' => now()->toIso8601String(),
                'queue_entry' => $dto->toArray(),
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Queue] status failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'queue_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '取得に失敗しました'], 500);
        }
    }
}
