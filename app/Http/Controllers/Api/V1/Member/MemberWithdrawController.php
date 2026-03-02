<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Member\MemberWithdrawUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberWithdrawController extends BaseApiController
{
    /** @var MemberWithdrawUseCase */
    private $withdrawUseCase;

    public function __construct(MemberWithdrawUseCase $withdrawUseCase)
    {
        $this->withdrawUseCase = $withdrawUseCase;
    }

    /**
     * 退会処理。User と Member をソフトデリートし、セッションをクリアする。
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $ok = $this->withdrawUseCase->handle($request);
            if (! $ok) {
                return $this->error('ユーザーを特定できません。ログインし直してください。', 401);
            }

            return $this->success([], '退会が完了しました。');
        } catch (\Exception $e) {
            LogHelper::error('[Member] withdraw failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return $this->error('退会処理に失敗しました。しばらくしてから再度お試しください。', 500);
        }
    }
}
