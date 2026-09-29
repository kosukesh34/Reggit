<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\UserService;
use App\UseCases\Order\Data\OrderFactory;
use App\UseCases\Payment\PaymentConfirmUseCase;
use App\UseCases\Payment\PaymentGateway;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentConfirmCheckoutController extends Controller
{
    public function __construct(
        private PaymentGateway $gateway,
        private PaymentConfirmUseCase $confirmUseCase,
        private UserService $userService,
        private OrderFactory $orderFactory,
    ) {}

    public function __invoke(Request $request)
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $request->validate([
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')->where('project_id', $projectId)],
        ]);

        $user = $this->userService->findUser($request);
        if ($user === null) {
            return response()->json(['error' => 'ユーザーを特定できません'], 401);
        }

        $order = Order::where('project_id', $projectId)->where('user_id', $user->id)
            ->find($request->integer('order_id'));
        $sessionId = $order?->payment_session['result']['id'] ?? null;
        if ($order === null || ($order->payment_session['channel'] ?? null) !== 'checkout' || ! is_string($sessionId)) {
            return response()->json(['error' => '決済情報が見つかりません'], 404);
        }

        try {
            $session = $this->gateway->retrieveCheckoutSession($sessionId);
            if (($session['payment_status'] ?? null) !== 'paid' || empty($session['payment_intent_id'])) {
                return response()->json(['success' => false, 'message' => '決済が完了していません'], 409);
            }

            $result = $this->confirmUseCase->handle(
                $session['payment_intent_id'], (int) $order->id, $projectId, (int) $user->id,
            );
            if (! $result->isSuccess) {
                return response()->json(['success' => false, 'message' => $result->message], 400);
            }

            return response()->json([
                'success' => true,
                'order' => $result->order !== null ? $this->orderFactory->create($result->order)->toArray() : null,
            ]);
        } catch (\Throwable $e) {
            LogHelper::error('[Payment] checkout confirm failed', [
                'order_id' => $order->id, 'project_id' => $projectId, 'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '決済の確認に失敗しました'], 503);
        }
    }
}
