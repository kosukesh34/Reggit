<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberUserInput;
use App\UseCases\Member\MemberPointsUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberPointsController extends Controller
{
    public function __construct(
        private MemberPointsUseCase $pointsUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $result = $this->pointsUseCase->handle(new MemberUserInput($user));
            if ($result->hasMember === false) {
                return response()->json([
                    'error' => '会員登録が必要です',
                ], 404);
            }

            return response()->json([
                'points' => $result->points,
                'transactions' => $result->transactions,
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Member] points failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'ポイント情報の取得に失敗しました',
            ], 500);
        }
    }
}
