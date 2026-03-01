<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\UseCases\Member\MemberWithdrawUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MemberWithdrawController extends Controller
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
            if (!$ok) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザーを特定できません。ログインし直してください。',
                ], 401);
            }
            return response()->json([
                'success' => true,
                'message' => '退会が完了しました。',
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Member] withdraw failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => '退会処理に失敗しました。しばらくしてから再度お試しください。',
            ], 500);
        }
    }
}
