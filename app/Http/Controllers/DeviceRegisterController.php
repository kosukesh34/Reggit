<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceRegisterRequest;
use App\Services\UserService;
use App\UseCases\Device\Data\DeviceRegisterInput;
use App\UseCases\Device\DeviceRegisterUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\JsonResponse;

class DeviceRegisterController extends Controller
{
    public function __construct(
        private UserService $userService,
        private DeviceRegisterUseCase $deviceRegisterUseCase,
    ) {}

    public function __invoke(DeviceRegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $pushTokenRaw = $validated['push_token'] ?? null;
        $hasPushToken = is_string($pushTokenRaw) && trim($pushTokenRaw) !== '';

        LogHelper::info('[Device] register request', [
            'project_id' => $projectId,
            'has_push_token' => $hasPushToken,
            'push_token_length' => $hasPushToken ? strlen(trim($pushTokenRaw)) : 0,
            'platform' => $validated['platform'] ?? null,
            'device_identifier_preview' => isset($validated['device_identifier'])
                ? substr((string) $validated['device_identifier'], 0, 8).'...'
                : null,
        ]);

        try {
            $user = $this->userService->getOrCreateUser($request);
            $output = $this->deviceRegisterUseCase->handle(new DeviceRegisterInput($user, $validated));

            LogHelper::info('[Device] register success', [
                'project_id' => $projectId,
                'device_id' => $output->deviceId,
                'user_id' => $user->id,
                'push_token_saved_length' => $output->savedPushTokenLength,
            ]);

            if ($output->hasPushTokenWarning) {
                LogHelper::warning('[Device] push_token was sent but not saved', [
                    'project_id' => $projectId,
                    'device_id' => $output->deviceId,
                ]);
            }

            return response()->json([
                'success' => true,
                'device_id' => $output->deviceId,
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
