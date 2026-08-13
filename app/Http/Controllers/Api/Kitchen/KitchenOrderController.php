<?php

namespace App\Http\Controllers\Api\Kitchen;

use App\Http\Controllers\Controller;
use App\UseCases\Kitchen\KitchenListOrdersUseCase;
use App\UseCases\Kitchen\KitchenUpdateOrderStatusUseCase;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class KitchenOrderController extends Controller
{
    public function index(KitchenListOrdersUseCase $useCase): JsonResponse
    {
        $orders = $useCase->handle(AdminContext::requireCurrentProjectId());

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    public function updateStatus(Request $request, string $projectKey, int $id, KitchenUpdateOrderStatusUseCase $useCase): JsonResponse
    {
        try {
            $order = $useCase->handle(
                AdminContext::requireCurrentProjectId(),
                $id,
                trim((string) $request->input('status', '')),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }
}
