<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\UseCases\Queue\QueueQrStatusUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueQrStatusController extends Controller
{
    public function __construct(
        private QueueQrStatusUseCase $qrStatusUseCase
    ) {}

    public function __invoke(Request $request, string $token)
    {
        try {
            $dto = $this->qrStatusUseCase->handle($request, $token);
            if ($dto === null) {
                return response()->json(['error' => '対象が見つかりません。'], 404);
            }

            return response()->json([
                'updated_at' => now()->toIso8601String(),
                'queue_entry' => $dto->toArray(),
            ]);
        } catch (\RuntimeException $e) {
            LogHelper::warning('[Queue] qr status blocked', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            LogHelper::error('[Queue] qr status failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '取得に失敗しました'], 500);
        }
    }
}
