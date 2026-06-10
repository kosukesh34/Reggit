<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberUserInput;
use App\UseCases\Member\MemberMeUseCase;
use App\Util\AdminContext;
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
            $user = $this->userService->getOrCreateUser($request);
            $dto = $this->meUseCase->handle(new MemberUserInput($user));

            return response()->json($dto->toArray());
        } catch (\Exception $e) {
            LogHelper::error('[Member] me failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => '認証が必要です',
                'is_member' => false,
            ], 401);
        }
    }
}
