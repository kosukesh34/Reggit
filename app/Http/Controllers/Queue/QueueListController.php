<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\UseCases\Queue\QueueListUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\JsonResponse;

class QueueListController extends Controller
{
    public function __construct(
        private QueueListUseCase $listUseCase
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $projectId = AdminContext::getCurrentProjectId();
            if ($projectId === null) {
                return response()->json(['error' => 'プロジェクトを指定してください'], 400);
            }

            $entries = $this->listUseCase->handle($projectId);

            return response()->json([
                'updated_at' => now()->toIso8601String(),
                'entries' => $entries,
            ]);
        } catch (\Throwable $e) {
            LogHelper::error('[Queue] list failed', [
                'project_id' => AdminContext::getCurrentProjectId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '一覧の取得に失敗しました'], 500);
        }
    }
}
