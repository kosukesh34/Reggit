<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\UserService;
use App\UseCases\Payment\PaymentConfirmUseCase;
use App\UseCases\Payment\PaymentCreateIntentUseCase;
use App\UseCases\Payment\PaymentSessionInProgressException;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class PaymentController extends BaseApiController
{
    public function __construct(
        private PaymentCreateIntentUseCase $createIntentUseCase,
        private PaymentConfirmUseCase $confirmUseCase,
        private UserService $userService,
    ) {}

    public function createPaymentIntent(Request $request)
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
            return $this->error('ユーザーを特定できません', 401);
        }

        try {
            $dto = $this->createIntentUseCase->handle(
                (int) $request->order_id,
                $projectId,
                (int) $user->id,
            );

            return $this->success($dto->toArray());
        } catch (PaymentSessionInProgressException $e) {
            return $this->error($e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
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
        $projectId = AdminContext::requireCurrentProjectId();
        $request->validate([
            'payment_intent_id' => 'required|string',
            'order_id' => [
                'required',
                Rule::exists('orders', 'id')->where('project_id', $projectId),
            ],
        ]);

        $user = $this->userService->findUser($request);
        if ($user === null) {
            return $this->error('ユーザーを特定できません', 401);
        }

        try {
            $result = $this->confirmUseCase->handle(
                $request->payment_intent_id,
                (int) $request->order_id,
                $projectId,
                (int) $user->id,
            );
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
