<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberWithdrawInput;
use App\UseCases\Member\MemberWithdrawUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberWithdrawController extends Controller
{
    public function __construct(
        private MemberWithdrawUseCase $withdrawUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->userService->findUser($request);
        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'ユーザーを特定できません。ログインし直してください。',
            ], 401);
        }

        try {
            $this->withdrawUseCase->handle(new MemberWithdrawInput($user));
            $this->userService->clearUserSession($request);

            return response()->json([
                'success' => true,
                'message' => '退会が完了しました。',
            ]);
        } catch (\RuntimeException $e) {
            LogHelper::error('[Member] withdraw failed (Smaregi or business)', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            LogHelper::error('[Member] withdraw failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '退会処理に失敗しました。しばらくしてから再度お試しください。',
            ], 500);
        }
    }
}
