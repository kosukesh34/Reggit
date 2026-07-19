<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\Services\DeviceService;
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
        private DeviceService $deviceService,
    ) {}

    public function __invoke(Request $request, string $token)
    {
        try {
            $projectId = AdminContext::getCurrentProjectId() ?? 1;
            $contextUser = $this->resolveContextUser($request);
            $deviceIdentifier = trim((string) ($request->header('X-Device-Identifier') ?? ''));
            $result = $this->qrStatusUseCase->handle(new QueueQrStatusInput(
                $token,
                $projectId,
                QueueRequestContext::resolveLineUserId($request, $contextUser?->line_user_id),
                $contextUser,
                $deviceIdentifier !== '' ? $deviceIdentifier : null,
                trim((string) ($request->header('X-Device-Platform') ?? '')) ?: null,
                $request->header('X-Device-Os-Version'),
                $request->header('X-Device-App-Version'),
            ));
            if ($result === null) {
                return response()->json(['error' => '対象が見つかりません。'], 404);
            }

            $this->sessionStore->applyQrOutput($request, $result);
            $this->ensureDeviceLinked(
                $request,
                $contextUser ?? $this->userService->findUser($request)
            );

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

    private function ensureDeviceLinked(Request $request, ?\App\Models\User $user): void
    {
        if ($user === null) {
            return;
        }
        $deviceIdentifier = trim((string) ($request->header('X-Device-Identifier') ?? ''));
        if ($deviceIdentifier === '') {
            return;
        }

        $this->deviceService->register($user, [
            'device_identifier' => $deviceIdentifier,
            'platform' => trim((string) ($request->header('X-Device-Platform') ?? 'unknown')) ?: 'unknown',
            'os_version' => $request->header('X-Device-Os-Version'),
            'app_version' => $request->header('X-Device-App-Version'),
        ]);
    }

    private function resolveContextUser(Request $request): ?\App\Models\User
    {
        $user = $this->userService->findUser($request);
        if ($user !== null) {
            return $user;
        }

        $deviceIdentifier = trim((string) ($request->header('X-Device-Identifier') ?? ''));
        if ($deviceIdentifier === '') {
            return null;
        }

        return $this->userService->getOrCreateUser($request);
    }
}
