<?php

namespace App\Http\Controllers\Api\Kitchen;

use App\Http\Controllers\Controller;
use App\UseCases\Kitchen\KitchenRegisterDeviceUseCase;
use App\UseCases\Kitchen\KitchenUnregisterDeviceUseCase;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class KitchenDeviceController extends Controller
{
    public function store(Request $request, KitchenRegisterDeviceUseCase $useCase): JsonResponse
    {
        $pushToken = trim((string) $request->input('push_token', ''));
        $platform = trim((string) $request->input('platform', 'ios'));
        $deviceName = $request->input('device_name');
        $deviceName = is_string($deviceName) ? trim($deviceName) : null;

        try {
            $device = $useCase->handle(
                AdminContext::requireCurrentProjectId(),
                $pushToken,
                $platform !== '' ? $platform : 'ios',
                $deviceName !== null && $deviceName !== '' ? $deviceName : null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'device' => [
                'id' => (int) $device->id,
                'platform' => (string) $device->platform,
                'device_name' => $device->device_name,
            ],
        ], 201);
    }

    public function destroy(Request $request, KitchenUnregisterDeviceUseCase $useCase): JsonResponse
    {
        $useCase->handle(
            AdminContext::requireCurrentProjectId(),
            trim((string) $request->input('push_token', '')),
        );

        return response()->json(['success' => true]);
    }
}
