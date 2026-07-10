<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Support\QueueRequestContext;
use App\Support\QueueSessionStore;
use App\UseCases\Queue\Data\QueueQrStatusInput;
use App\UseCases\Queue\QueueQrStatusUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueQrStatusController extends Controller
{
    public function __construct(
        private QueueQrStatusUseCase $qrStatusUseCase,
        private UserService $userService,
        private QueueSessionStore $sessionStore,
    ) {}

    public function __invoke(Request $request, string $token)
    {
        try {
            $projectId = AdminContext::getCurrentProjectId() ?? 1;
            $contextUser = $this->userService->findUser($request);
            $result = $this->qrStatusUseCase->handle(new QueueQrStatusInput(
                $token,
                $projectId,
                QueueRequestContext::resolveLineUserId($request, $contextUser?->line_user_id),
                $contextUser,
            ));
            if ($result === null) {
                return response()->json(['error' => '対象が見つかりません。'], 404);
            }

            $this->sessionStore->applyQrOutput($request, $result);

            return response()->json([
                'updated_at' => now()->toIso8601String(),
                'queue_entry' => $result->queueEntry->toArray(),
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
