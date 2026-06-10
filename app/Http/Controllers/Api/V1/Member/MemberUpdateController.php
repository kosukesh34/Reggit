<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Services\MemberService;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberUpdateInput;
use App\UseCases\Member\MemberUpdateUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MemberUpdateController extends BaseApiController
{
    public function __construct(
        private MemberUpdateUseCase $updateUseCase,
        private UserService $userService,
        private MemberService $memberService,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $member = $user->member;
            if ($member === null) {
                return $this->error('会員登録が必要です', 400);
            }

            $validated = $request->validate(
                $this->memberService->getProfileValidationRules((int) $member->project_id)
            );
            $dto = $this->updateUseCase->handle(new MemberUpdateInput($user, $validated));

            return $this->success(['member' => $dto->toArray()], '更新しました');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            LogHelper::error('[Member] update failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage(), 400);
        }
    }
}
