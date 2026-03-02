<?php

namespace App\Http\Controllers\Payment;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\UseCases\Payment\PaymentCreateIntentUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentCreateIntentController extends Controller
{
    public function __construct(
        private PaymentCreateIntentUseCase $createIntentUseCase
    ) {}

    public function __invoke(Request $request)
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'order_id' => [
                'nullable',
                Rule::exists('orders', 'id')->where('project_id', $projectId),
            ],
        ]);

        try {
            if ($request->filled('order_id')) {
                $order = Order::where('project_id', $projectId)->findOrFail((int) $request->order_id);
                if ($order->order_type !== OrderType::MOBILE || $order->payment_method !== PaymentMethod::STRIPE) {
                    return response()->json(['error' => 'モバイル注文（注文時決済）のみ決済を作成できます'], 422);
                }
            }
            $dto = $this->createIntentUseCase->handle((float) $request->amount, $request->order_id);

            return response()->json($dto->toArray());
        } catch (\Exception $e) {
            LogHelper::error('[Payment] create intent failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '決済の作成に失敗しました'], 400);
        }
    }
}
