<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\UseCases\Queue\QueueCancelUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueCancelController extends Controller
{
    public function __construct(
        private QueueCancelUseCase $cancelUseCase
    ) {}

    public function __invoke(Request $request)
    {
        $id = (int) $request->route('id');
        try {
            $result = $this->cancelUseCase->handle(
                $id,
                AdminContext::requireCurrentProjectId(),
            );
            if ($result->entry === null) {
                return response()->json(['error' => '対象が見つかりません。'], 404);
            }
            if ($result->isConflict) {
                return response()->json(['error' => 'キャンセルできません。'], 409);
            }

            $entry = $result->entry;

            return response()->json([
                'message' => 'キャンセルしました',
                'queue_entry' => [
                    'id' => $entry->id,
                    'queue_number' => $entry->queue_number,
                    'formatted_queue_number' => $entry->formattedQueueNumber(),
                    'status' => $entry->status,
                    'party_size' => $entry->party_size,
                    'updated_at' => $entry->updated_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Queue] cancel failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'キャンセルに失敗しました'], 500);
        }
    }
}
