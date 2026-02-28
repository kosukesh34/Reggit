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
    ) {
    }

    public function __invoke(DeviceRegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $device = $this->deviceService->register($user, $request->validated());

            return response()->json([
                'success' => true,
                'device_id' => $device->id,
            ]);
        } catch (\Throwable $e) {
            LogHelper::error('[Device] register failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '端末情報の登録に失敗しました',
            ], 500);
        }
    }
}

