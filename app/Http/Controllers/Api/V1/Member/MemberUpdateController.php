<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\MemberProfileRequest;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberUpdateInput;
use App\UseCases\Member\MemberUpdateUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;

class MemberUpdateController extends BaseApiController
{
    public function __construct(
        private MemberUpdateUseCase $updateUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(MemberProfileRequest $request)
    {
        try {
            $dto = $this->updateUseCase->handle(new MemberUpdateInput(
                $this->userService->getOrCreateUser($request),
                $request->validated()
            ));

            return $this->success(['member' => $dto->toArray()], '更新しました');
        } catch (\Exception $e) {
            LogHelper::error('[Member] update failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage(), 400);
        }
    }
}
