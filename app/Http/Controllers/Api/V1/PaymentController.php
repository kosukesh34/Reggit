<?php

namespace App\Http\Controllers\Api\V1;

use App\Util\AdminContext;
use App\UseCases\Payment\PaymentConfirmUseCase;
use App\UseCases\Payment\PaymentCreateIntentUseCase;
use Illuminate\Http\Request;
use App\Util\LogHelper;
use Illuminate\Validation\Rule;

class PaymentController extends BaseApiController
{
    public function __construct(
        private PaymentCreateIntentUseCase $createIntentUseCase,
        private PaymentConfirmUseCase $confirmUseCase
    ) {
    }

    public function createPaymentIntent(Request $request)
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
            $dto = $this->createIntentUseCase->handle((float) $request->amount, $request->order_id);
            return $this->success($dto->toArray());
        } catch (\Exception $e) {
            LogHelper::error('[Payment] create intent failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
            return $this->error('決済の作成に失敗しました', 400);
        }
    }

    public function confirmPayment(Request $request)
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
                return $this->error($result->message, 400, ['status' => $result->status]);
            }
            return $this->success(['order' => $result->order], $result->message);
        } catch (\Exception $e) {
            LogHelper::error('[Payment] confirm failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
            return $this->error('決済の確認に失敗しました', 400);
        }
    }
}
