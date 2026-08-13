<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberProfileRequest;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberRegisterInput;
use App\UseCases\Member\MemberRegisterUseCase;
use App\Util\LogHelper;

class MemberRegisterController extends Controller
{
    public function __construct(
        private MemberRegisterUseCase $registerUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(MemberProfileRequest $request)
    {
        $user = $this->userService->getOrCreateUser($request);

        try {
            $result = $this->registerUseCase->handle(new MemberRegisterInput($user, $request->validated()));
            $status = $result->alreadyRegistered ? 200 : 201;

            return response()->json($result->member->toArray(), $status);
        } catch (\Exception $e) {
            LogHelper::error('[Member] register failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
