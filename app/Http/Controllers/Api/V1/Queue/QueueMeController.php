<?php

namespace App\Http\Controllers\Api\V1\Queue;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Queue\QueueMeUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueMeController extends BaseApiController
{
    public function __construct(
        private QueueMeUseCase $meUseCase
    ) {
    }

    public function __invoke(Request $request)
    {
        try {
            $result = $this->meUseCase->handle($request);
            if ($result->queueEntry === null) {
                return $this->success([
                    'queue_entry' => null,
                    'message' => '待ち番号はありません',
                    'waiting_count' => $result->waitingCount,
                    'estimated_wait_minutes' => $result->estimatedWaitMinutes,
                    'updated_at' => now()->toIso8601String(),
                ]);
            }
            return $this->success([
                'queue_entry' => $result->queueEntry->toArray(),
                'updated_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Queue] me failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return $this->error('取得に失敗しました', 500);
        }
    }
}
