<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberAddPointsInput;
use App\UseCases\Member\MemberAddPointsUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberAddPointsController extends Controller
{
    public function __construct(
        private MemberAddPointsUseCase $addPointsUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        try {
            $user = $this->userService->getOrCreateUser($request);
            $result = $this->addPointsUseCase->handle(new MemberAddPointsInput(
                $user,
                (int) $validated['points'],
                $validated['description'] ?? null,
            ));
            if ($result->hasMember === false) {
                return response()->json([
                    'error' => '会員登録が必要です',
                ], 404);
            }

            return response()->json([
                'message' => 'ポイントが追加されました',
                'points' => $result->points,
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Member] add points failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'ポイントの追加に失敗しました',
            ], 500);
        }
    }
}
