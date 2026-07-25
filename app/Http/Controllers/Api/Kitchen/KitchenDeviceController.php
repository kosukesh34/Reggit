<?php

namespace App\Http\Controllers\Api\Kitchen;

use App\Http\Controllers\Controller;
use App\Services\Kitchen\KitchenOrderService;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class KitchenDeviceController extends Controller
{
    public function __construct(
        private KitchenOrderService $kitchenOrderService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $pushToken = trim((string) $request->input('push_token', ''));
        $platform = trim((string) $request->input('platform', 'ios'));
        $deviceName = $request->input('device_name');
        $deviceName = is_string($deviceName) ? trim($deviceName) : null;
        $projectId = (int) AdminContext::getCurrentProjectId();

        try {
            $device = $this->kitchenOrderService->registerDevice(
                $projectId,
                $pushToken,
                $platform !== '' ? $platform : 'ios',
                $deviceName !== '' ? $deviceName : null
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

    public function destroy(Request $request): JsonResponse
    {
        $pushToken = trim((string) $request->input('push_token', ''));
        $projectId = (int) AdminContext::getCurrentProjectId();
        $this->kitchenOrderService->unregisterDevice($projectId, $pushToken);

        return response()->json(['success' => true]);
    }
}
