<?php

namespace App\UseCases\Member;

use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\UserService;
use App\UseCases\Member\Exceptions\MemberWithdrawalException;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;

class MemberWithdrawUseCase
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
        private UserService $userService,
    ) {}

    /**
     * ユーザーを退会（ソフトデリート）する。
     * 会員があればスマレジ連携済みの場合は先にスマレジ会員を削除してからソフトデリートする。
     * セッションクリアは Controller で行う。
     */
    public function handle(User $user): void
    {
        $projectId = AdminContext::getCurrentProjectId() ?? (int) $user->project_id;

        $member = $user->member;
        if ($member !== null) {
            $smaregiCustomerId = $member->smaregi_customer_id ?? '';
            $contractId = $this->smaregiApi->getContractIdForProject($projectId);
            $contractId = is_string($contractId) ? trim($contractId) : '';
            if ($contractId !== '' && $smaregiCustomerId !== null && $smaregiCustomerId !== '') {
                $result = $this->smaregiApi->deleteCustomer($contractId, $smaregiCustomerId);
                if ($result === null) {
                    throw new MemberWithdrawalException('外部会員連携の解除に失敗しました。しばらくしてから再度お試しください。');
                }
            }
        }

        DB::transaction(function () use ($member, $projectId, $user): void {
            if ($member !== null) {
                $member->delete();
            }

            try {
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            } catch (\Throwable $exception) {
                LogHelper::warning('[Member] token cleanup skipped', [
                    'project_id' => $projectId,
                    'user_id' => $user->id,
                    'message' => $exception->getMessage(),
                ]);
            }

            $this->userService->releaseLineUserIdForWithdraw($user);
            $user->delete();
        });

        LogHelper::info('[Member] user withdrew', [
            'project_id' => $projectId,
            'user_id' => $user->id,
        ]);
    }
}
