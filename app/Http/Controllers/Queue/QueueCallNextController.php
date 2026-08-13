<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\UseCases\Queue\QueueCallNextUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueCallNextController extends Controller
{
    public function __construct(
        private QueueCallNextUseCase $callNextUseCase
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $dto = $this->callNextUseCase->handle(AdminContext::requireCurrentProjectId());
            if ($dto === null) {
                return response()->noContent();
            }

            return response()->json([
                'message' => '呼び出しました',
                'queue_entry' => $dto->toArray(),
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Queue] callNext failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '呼び出しに失敗しました'], 500);
        }
    }
}
