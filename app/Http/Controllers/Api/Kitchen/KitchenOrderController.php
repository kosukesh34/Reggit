<?php

namespace App\Http\Controllers\Api\Kitchen;

use App\Http\Controllers\Controller;
use App\Services\Kitchen\KitchenOrderService;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class KitchenOrderController extends Controller
{
    public function __construct(
        private KitchenOrderService $kitchenOrderService,
    ) {}

    public function index(): JsonResponse
    {
        $projectId = (int) AdminContext::getCurrentProjectId();
        $orders = $this->kitchenOrderService->listActiveOrders($projectId);

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    public function updateStatus(Request $request, string $projectKey, int $id): JsonResponse
    {
        $status = trim((string) $request->input('status', ''));
        $projectId = (int) AdminContext::getCurrentProjectId();

        try {
            $order = $this->kitchenOrderService->updateStatus($projectId, $id, $status);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }
}
