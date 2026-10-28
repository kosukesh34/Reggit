<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberProfileRequest;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberRegisterInput;
use App\UseCases\Member\MemberRegisterUseCase;
use App\UseCases\Member\RecordAcquisitionSourceUseCase;
use App\Util\LogHelper;

class MemberRegisterController extends Controller
{
    public function __construct(
        private MemberRegisterUseCase $registerUseCase,
        private UserService $userService,
        private RecordAcquisitionSourceUseCase $recordSourceUseCase,
    ) {}

    public function __invoke(MemberProfileRequest $request)
    {
        $user = $this->userService->getOrCreateUser($request);

        try {
            $result = $this->registerUseCase->handle(new MemberRegisterInput($user, $request->validated()));
            $status = $result->alreadyRegistered ? 200 : 201;
            $source = $request->input('acquisition_source');
            if (! $result->alreadyRegistered && is_string($source) && preg_match(RecordAcquisitionSourceUseCase::CODE_PATTERN, $source) === 1) {
                $this->recordAcquisitionSource((int) $user->project_id, (int) $user->id, $source);
            }

            return response()->json($result->member->toArray(), $status);
        } catch (\Exception $e) {
            LogHelper::error('[Member] register failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /** 登録元の記録は集計用のため、失敗しても登録自体は成功させる */
    private function recordAcquisitionSource(int $projectId, int $userId, string $code): void
    {
        try {
            $this->recordSourceUseCase->handle($projectId, $userId, $code);
        } catch (\Throwable $e) {
            LogHelper::warning('[Member] acquisition source record failed', ['project_id' => $projectId, 'user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
}
