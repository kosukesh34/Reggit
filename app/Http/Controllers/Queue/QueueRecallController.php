<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\UseCases\Queue\QueueRecallUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueRecallController extends Controller
{
    public function __construct(
        private QueueRecallUseCase $recallUseCase
    ) {}

    public function __invoke(Request $request, int $id)
    {
        try {
            $dto = $this->recallUseCase->handle($id, AdminContext::requireCurrentProjectId());
            if ($dto === null) {
                return response()->json(['error' => '受付が見つかりません'], 404);
            }

            return response()->json([
                'message' => '再呼び出ししました',
                'queue_entry' => $dto->toArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            LogHelper::error('[Queue] recall failed', [
                'queue_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '再呼び出しに失敗しました'], 500);
        }
    }
}
