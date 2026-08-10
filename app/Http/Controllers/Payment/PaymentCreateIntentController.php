<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\UserService;
use App\UseCases\Payment\PaymentCreateIntentUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class PaymentCreateIntentController extends Controller
{
    public function __construct(
        private PaymentCreateIntentUseCase $createIntentUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request)
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $request->validate([
            'order_id' => [
                'required',
                'integer',
                Rule::exists('orders', 'id')->where('project_id', $projectId),
            ],
        ]);

        $user = $this->userService->findUser($request);
        if ($user === null) {
            return response()->json(['error' => 'ユーザーを特定できません'], 401);
        }

        try {
            $order = Order::where('project_id', $projectId)->findOrFail((int) $request->order_id);
            $dto = $this->createIntentUseCase->handle($order, (int) $user->id);

            return response()->json($dto->toArray());
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            LogHelper::error('[Payment] create intent failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '決済の作成に失敗しました'], 400);
        }
    }
}
