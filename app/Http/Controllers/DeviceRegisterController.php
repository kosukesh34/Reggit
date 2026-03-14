<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceRegisterRequest;
use App\Services\DeviceService;
use App\Services\UserService;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\JsonResponse;

class DeviceRegisterController extends Controller
{
    public function __construct(
        private UserService $userService,
        private DeviceService $deviceService
    ) {}

    public function __invoke(DeviceRegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $pushTokenRaw = $validated['push_token'] ?? null;
        $hasPushToken = is_string($pushTokenRaw) && trim($pushTokenRaw) !== '';
        $pushTokenLen = $hasPushToken ? strlen(trim($pushTokenRaw)) : 0;

        LogHelper::info('[Device] register request', [
            'project_id' => $projectId,
            'has_push_token' => $hasPushToken,
            'push_token_length' => $pushTokenLen,
            'platform' => $validated['platform'] ?? null,
            'device_identifier_preview' => isset($validated['device_identifier'])
                ? substr((string) $validated['device_identifier'], 0, 8).'...'
                : null,
        ]);

        try {
            $user = $this->userService->getOrCreateUser($request);
            $device = $this->deviceService->register($user, $validated);

            $savedTokenLen = $device->push_token !== null && $device->push_token !== ''
                ? strlen($device->push_token)
                : 0;
            LogHelper::info('[Device] register success', [
                'project_id' => $projectId,
                'device_id' => $device->id,
                'user_id' => $device->user_id,
                'push_token_saved_length' => $savedTokenLen,
            ]);

            if ($hasPushToken && $savedTokenLen === 0) {
                LogHelper::warning('[Device] push_token was sent but not saved', [
                    'project_id' => $projectId,
                    'device_id' => $device->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'device_id' => $device->id,
            ]);
        } catch (\Throwable $e) {
            LogHelper::error('[Device] register failed', [
                'project_id' => $projectId,
                'has_push_token' => $hasPushToken,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '端末情報の登録に失敗しました',
            ], 500);
        }
    }
}
