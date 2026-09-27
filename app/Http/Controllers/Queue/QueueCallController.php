<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\UseCases\Queue\QueueCallUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\JsonResponse;

class QueueCallController extends Controller
{
    public function __construct(private QueueCallUseCase $callUseCase) {}

    public function __invoke(int $id): JsonResponse
    {
        try {
            $dto = $this->callUseCase->handle($id, AdminContext::requireCurrentProjectId());
            if ($dto === null) {
                return response()->json(['error' => '受付が見つかりません'], 404);
            }

            return response()->json(['message' => '呼び出しました', 'queue_entry' => $dto->toArray()]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            LogHelper::error('[Queue] call failed', ['queue_id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['error' => '呼び出しに失敗しました'], 500);
        }
    }
}
