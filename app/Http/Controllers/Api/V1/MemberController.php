<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\MemberService;
use App\Services\UserService;
use App\UseCases\Member\MemberAddPointsUseCase;
use App\UseCases\Member\MemberMeUseCase;
use App\UseCases\Member\MemberPointsUseCase;
use App\UseCases\Member\MemberRegisterUseCase;
use App\UseCases\Member\MemberUpdateUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * 会員 API（レガシー互換）。責務は UseCase に委譲する。
 */
class MemberController extends BaseApiController
{
    public function __construct(
        private MemberMeUseCase $meUseCase,
        private MemberUpdateUseCase $updateUseCase,
        private MemberRegisterUseCase $registerUseCase,
        private MemberPointsUseCase $pointsUseCase,
        private MemberAddPointsUseCase $addPointsUseCase,
        private UserService $userService,
        private MemberService $memberService
    ) {}

    public function me(Request $request)
    {
        try {
            $dto = $this->meUseCase->handle($request);

            return $this->success(['member' => $dto->toArray()]);
        } catch (\Exception $e) {
            Log::error('Failed to get member data: '.$e->getMessage());

            return $this->error('認証が必要です', 401);
        }
    }

    public function update(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $member = $user->member;
            if ($member === null) {
                return $this->error('会員登録が必要です', 400);
            }

            $rules = $this->memberService->getProfileValidationRules((int) $member->project_id);
            $validated = $request->validate($rules);

            $dto = $this->updateUseCase->handle($request, $validated);

            return $this->success(['member' => $dto->toArray()], '更新しました');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Member profile update error: '.$e->getMessage());

            return $this->error($e->getMessage(), 400);
        }
    }

    public function register(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $projectId = (int) ($user->project_id ?? 1);
            $rules = $this->memberService->getProfileValidationRules($projectId);
            $validated = $request->validate($rules);

            $output = $this->registerUseCase->handle($request, $validated);
            $message = $output->alreadyRegistered ? '既に会員登録済みです' : '会員登録が完了しました';
            $status = $output->alreadyRegistered ? 200 : 201;

            return $this->success(['member' => $output->member->toArray()], $message, $status);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Member registration error: '.$e->getMessage());

            return $this->error($e->getMessage(), 400);
        }
    }

    public function getPoints(Request $request)
    {
        try {
            $output = $this->pointsUseCase->handle($request);
            if (! $output->hasMember) {
                return $this->error('会員登録が必要です', 404);
            }

            return $this->success([
                'points' => $output->points,
                'transactions' => $output->transactions->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get points: '.$e->getMessage());

            return $this->error('ポイント情報の取得に失敗しました', 500);
        }
    }

    public function addPoints(Request $request)
    {
        if (! app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $validated = $request->validate([
            'points' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        try {
            $output = $this->addPointsUseCase->handle(
                $request,
                (int) $validated['points'],
                isset($validated['description']) ? (string) $validated['description'] : null
            );
            if (! $output->hasMember) {
                return $this->error('会員登録が必要です', 404);
            }

            return $this->success([
                'points' => $output->points,
            ], 'ポイントが追加されました');
        } catch (\Exception $e) {
            Log::error('Failed to add points: '.$e->getMessage());

            return $this->error('ポイントの追加に失敗しました', 500);
        }
    }
}
