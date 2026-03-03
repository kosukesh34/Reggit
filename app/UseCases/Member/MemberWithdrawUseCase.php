<?php

namespace App\UseCases\Member;

use App\Models\ShopSetting;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\UserService;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class MemberWithdrawUseCase
{
    private UserService $userService;

    private SmaregiApiService $smaregiApi;

    public function __construct(UserService $userService, SmaregiApiService $smaregiApi)
    {
        $this->userService = $userService;
        $this->smaregiApi = $smaregiApi;
    }

    /**
     * 現在のユーザーを退会（ソフトデリート）する。
     * 会員（Member）があれば、スマレジ連携済みの場合は先にスマレジ会員を削除してからソフトデリートし、続けて User をソフトデリートする。
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

        // 会員があれば先にスマレジ会員削除（必要な場合）、続けてソフトデリート（User に hasOne Member）
        $member = $user->member;
        if ($member !== null) {
            $smaregiCustomerId = $member->smaregi_customer_id ?? '';
            $contractId = ShopSetting::getValue('smaregi_contract_id', null, $projectId);
            $contractId = is_string($contractId) ? trim($contractId) : '';
            if ($contractId !== '' && $smaregiCustomerId !== null && $smaregiCustomerId !== '') {
                $result = $this->smaregiApi->deleteCustomer($contractId, $smaregiCustomerId);
                if ($result === null) {
                    throw new \RuntimeException(
                        'スマレジの会員削除に失敗しました。ログ(storage/logs/laravel.log)の [Smaregi] を確認してください。'
                    );
                }
            }
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
