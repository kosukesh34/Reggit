<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\UseCases\Payment\PaymentConfirmUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentConfirmController extends Controller
{
    public function __construct(
        private PaymentConfirmUseCase $confirmUseCase
    ) {
    }

    public function __invoke(Request $request)
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $request->validate([
            'payment_intent_id' => 'required|string',
            'order_id' => [
                'required',
                Rule::exists('orders', 'id')->where('project_id', $projectId),
            ],
        ]);

        try {
            $result = $this->confirmUseCase->handle($request->payment_intent_id, (int) $request->order_id);
            if ($result->isSuccess === false) {
                return response()->json([
                    'success' => false,
                    'message' => $result->message,
                    'status' => $result->status,
                ], 400);
            }
            return response()->json([
                'success' => true,
                'message' => $result->message,
                'order' => $result->order,
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Payment] confirm failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => '決済の確認に失敗しました'], 400);
        }
    }
}
