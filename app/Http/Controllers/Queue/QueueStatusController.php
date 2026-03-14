<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\UseCases\Queue\QueueStatusUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueStatusController extends Controller
{
    public function __construct(
        private QueueStatusUseCase $queueStatusUseCase
    ) {}

    public function __invoke(Request $request, int $id)
    {
        try {
            $projectId = AdminContext::getCurrentProjectId() ?? 1;
            $dto = $this->queueStatusUseCase->handle($projectId, $id);

            if ($dto === null) {
                return response()->json(['error' => '対象が見つかりません。'], 404);
            }

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
