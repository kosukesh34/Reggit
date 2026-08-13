<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\MemberProfileRequest;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberRegisterInput;
use App\UseCases\Member\MemberRegisterUseCase;
use App\Util\LogHelper;

class MemberRegisterController extends BaseApiController
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
            if ($result->alreadyRegistered) {
                return $this->success(['member' => $result->member->toArray()], '既に会員登録済みです');
            }

            return $this->success(['member' => $result->member->toArray()], '会員登録が完了しました', 201);
        } catch (\Exception $e) {
            LogHelper::error('[Member] register failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage(), 400);
        }
    }
}
