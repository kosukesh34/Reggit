<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\UserDevice;
use App\Repositories\Contracts\UserDeviceRepository;
use App\Services\UserService;
use App\UseCases\Member\ClaimMemberTransferCodeUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class MemberTransferClaimController extends BaseApiController
{
    public function __construct(
        private UserService $userService,
        private UserDeviceRepository $userDeviceRepository,
        private ClaimMemberTransferCodeUseCase $claimMemberTransferCodeUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->userService->findUser($request);
        if ($user === null) {
            return $this->error('ユーザーを特定できません。ログインし直してください。', 401);
        }

        try {
            $device = $this->resolveDevice($request, $user->id);
            $this->claimMemberTransferCodeUseCase->handle(
                $user,
                $device,
                (string) $request->input('code', ''),
                (int) $request->input('member_id', 0),
            );

            return $this->success([], '会員を引き継ぎました');
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            LogHelper::error('[Member] transfer claim failed', [
                'project_id' => AdminContext::getCurrentProjectId(),
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('引き継ぎに失敗しました', 500);
        }
    }

    private function resolveDevice(Request $request, int $userId): ?UserDevice
    {
        $identifier = trim((string) $request->header('X-Device-Identifier'));
        if ($identifier === '') {
            return null;
        }

        $projectId = AdminContext::requireCurrentProjectId();
        $device = $this->userDeviceRepository->findLatestByProjectAndIdentifier($projectId, $identifier);
        $token = $request->header('X-Device-Token');
        $token = is_string($token) ? $token : null;
        if ($device === null || $device->authenticates($token) === false || (int) $device->user_id !== $userId) {
            throw new InvalidArgumentException('端末を確認できません。アプリを開き直してください');
        }

        return $device;
    }
}
