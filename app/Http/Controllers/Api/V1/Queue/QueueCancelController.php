<?php

namespace App\Http\Controllers\Api\V1\Queue;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Enums\QueueStatus;
use App\UseCases\Queue\QueueCancelUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueCancelController extends BaseApiController
{
    public function __construct(
        private QueueCancelUseCase $cancelUseCase
    ) {
    }

    public function __invoke(Request $request, int $id)
    {
        try {
            $entry = $this->cancelUseCase->handle($id);
            if ($entry === null) {
                return $this->error('対象が見つかりません。', 404);
            }

            if (!in_array($entry->status, [QueueStatus::WAITING, QueueStatus::CALLED, QueueStatus::CANCELLED], true)) {
                return $this->error('キャンセルできません。', 409);
            }

            return $this->success([
                'queue_entry' => [
                    'id' => $entry->id,
                    'queue_number' => $entry->queue_number,
                    'status' => $entry->status,
                    'party_size' => $entry->party_size,
                    'updated_at' => $entry->updated_at->toIso8601String(),
                ],
            ], 'キャンセルしました');
        } catch (\Exception $e) {
            LogHelper::error('[Queue] cancel failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return $this->error('キャンセルに失敗しました', 500);
        }
    }
}
