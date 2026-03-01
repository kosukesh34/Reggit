<?php

namespace App\UseCases\Member;

use App\Services\UserService;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberWithdrawUseCase
{
    /** @var UserService */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * 現在のユーザーを退会（ソフトデリート）する。
     * 会員（Member）があれば先にソフトデリートし、続けて User をソフトデリートする。
     * セッションをクリアし、Sanctum トークンがあれば削除する。
     *
     * @return bool 退会できた場合 true、ユーザーが特定できない場合 false
     */
    public function handle(Request $request): bool
    {
        $user = $this->userService->findUser($request);
        if ($user === null) {
            return false;
        }

        $projectId = AdminContext::getCurrentProjectId() ?? (int) $user->project_id;

        // 会員があれば先にソフトデリート（User に hasOne Member）
        $member = $user->member;
        if ($member !== null) {
            $member->delete();
        }

        // Sanctum トークンがあれば削除
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        // User をソフトデリート
        $user->delete();

        LogHelper::info('[Member] user withdrew', [
            'project_id' => $projectId,
            'user_id' => $user->id,
        ]);

        // セッションからユーザーIDを削除
        $this->userService->clearUserSession($request);

        return true;
    }
}
