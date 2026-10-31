<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Services\UserService;
use App\UseCases\Member\ConfirmExistingMemberLinkUseCase;
use App\UseCases\Member\ExistingMemberLinkFormUseCase;
use App\UseCases\Member\RequestExistingMemberLinkUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ExistingMemberLinkController extends BaseApiController
{
    public function __construct(
        private UserService $userService,
        private RequestExistingMemberLinkUseCase $requestUseCase,
        private ConfirmExistingMemberLinkUseCase $confirmUseCase,
        private ExistingMemberLinkFormUseCase $formUseCase,
    ) {}

    public function show(): JsonResponse
    {
        return $this->success($this->formUseCase->form(AdminContext::requireCurrentProjectId()));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->findUser($request);
        if ($user === null) {
            return $this->error('ユーザーを特定できません。ログインし直してください。', 401);
        }
        $projectId = AdminContext::requireCurrentProjectId();
        $fields = $this->formUseCase->identifierFields($projectId);
        if ($fields === null) {
            return $this->error('既存会員連携は利用できません', 422);
        }
        $input = [];
        foreach ($fields as $field) {
            $input[$field] = trim((string) $request->input($field, ''));
            if ($input[$field] === '') {
                return $this->error('必要な項目を入力してください', 422);
            }
        }

        try {
            $result = $this->requestUseCase->handle($projectId, $user, $input);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            LogHelper::error('[Member] existing link failed', [
                'project_id' => $projectId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('既存会員連携に失敗しました', 500);
        }

        return $this->success(['linked' => $result['linked']], $result['message']);
    }

    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:48', 'alpha_num:ascii']]);
        $user = $this->userService->findUser($request);
        if ($user === null) {
            return $this->error('ユーザーを特定できません。ログインし直してください。', 401);
        }
        try {
            $this->confirmUseCase->handle($data['token'], $user);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            LogHelper::error('[Member] existing link confirmation failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return $this->error('既存会員連携に失敗しました', 500);
        }

        return $this->success(['linked' => true], '既存会員を連携しました');
    }
}
