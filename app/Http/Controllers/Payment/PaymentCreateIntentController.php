<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Payment\PaymentCreateIntentUseCase;
use App\UseCases\Payment\PaymentSessionInProgressException;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
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
        $projectId = AdminContext::requireCurrentProjectId();
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
            $dto = $this->createIntentUseCase->handle(
                (int) $request->order_id,
                $projectId,
                $user,
            );

            return response()->json($dto->toArray());
        } catch (AuthenticationException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        } catch (PaymentSessionInProgressException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
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
