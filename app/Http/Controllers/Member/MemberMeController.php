<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Member\MemberMeUseCase;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberMeController extends Controller
{
    public function __construct(
        private MemberMeUseCase $meUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $dto = $this->meUseCase->handle($this->userService->getOrCreateUser($request));

            return response()->json($dto->toArray());
        } catch (\Exception $e) {
            LogHelper::error('[Member] me failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => '認証が必要です',
                'is_member' => false,
            ], 401);
        }
    }
}
