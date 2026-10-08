<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Payment\PaymentCreateCheckoutUseCase;
use App\UseCases\Payment\PaymentSessionInProgressException;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class PaymentCreateCheckoutController extends Controller
{
    public function __construct(
        private PaymentCreateCheckoutUseCase $useCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request)
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $request->validate([
            'surface' => ['nullable', Rule::in(['web', 'native'])],
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')->where('project_id', $projectId)],
        ]);
        $user = $this->userService->findUser($request);
        if ($user === null) {
            return response()->json(['error' => 'ユーザーを特定できません'], 401);
        }

        try {
            return response()->json($this->useCase->handle(
                (int) $request->order_id,
                $projectId,
                (int) $user->id,
                (string) $request->input('surface', 'native'),
            )->toArray());
        } catch (PaymentSessionInProgressException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            LogHelper::error('[Payment] checkout creation failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '決済画面の作成に失敗しました'], 400);
        }
    }
}
