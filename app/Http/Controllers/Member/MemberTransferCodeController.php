<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Services\UserService;
use App\UseCases\Member\IssueMemberTransferCodeUseCase;
use App\Util\LogHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class MemberTransferCodeController extends BaseApiController
{
    public function __construct(
        private UserService $userService,
        private IssueMemberTransferCodeUseCase $issueMemberTransferCodeUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->userService->findUser($request);
        if ($user === null) {
            return $this->error('ユーザーを特定できません。ログインし直してください。', 401);
        }

        try {
            $issued = $this->issueMemberTransferCodeUseCase->handle($user, null);

            return $this->success([
                'member_id' => $issued->memberId,
                'code' => $issued->code,
                'expires_at' => $issued->expiresAt,
            ], '引き継ぎコードを発行しました');
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            LogHelper::error('[Member] transfer code issue failed', [
                'project_id' => $user->project_id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('引き継ぎコードの発行に失敗しました', 500);
        }
    }
}
