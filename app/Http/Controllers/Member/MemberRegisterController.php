<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\MemberService;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberRegisterInput;
use App\UseCases\Member\MemberRegisterUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberRegisterController extends Controller
{
    public function __construct(
        private MemberRegisterUseCase $registerUseCase,
        private UserService $userService,
        private MemberService $memberService,
    ) {}

    public function __invoke(Request $request)
    {
        $user = $this->userService->getOrCreateUser($request);
        $projectId = (int) ($user->project_id ?? 1);
        $rules = $this->memberService->getProfileValidationRules($projectId);
        $validated = $request->validate($rules);

        try {
            $result = $this->registerUseCase->handle(new MemberRegisterInput($user, $validated));
            $status = $result->alreadyRegistered ? 200 : 201;

            return response()->json($result->member->toArray(), $status);
        } catch (\Exception $e) {
            LogHelper::error('[Member] register failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
