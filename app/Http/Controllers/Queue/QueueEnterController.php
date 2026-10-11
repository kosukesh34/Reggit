<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\UseCases\Queue\QueueEnterUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueEnterController extends Controller
{
    public function __construct(
        private QueueEnterUseCase $enterUseCase
    ) {}

    public function __invoke(Request $request)
    {
        $id = (int) $request->route('id');
        try {
            $dto = $this->enterUseCase->handle($id, AdminContext::requireCurrentProjectId());
            if ($dto === null) {
                return response()->json(['error' => '受付が見つかりません'], 404);
            }

            return response()->json([
                'message' => '入店しました',
                'queue_entry' => $dto->toArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            LogHelper::error('[Queue] enter failed', [
                'queue_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '入店処理に失敗しました'], 500);
        }
    }
}
