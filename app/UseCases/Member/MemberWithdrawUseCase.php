<?php

namespace App\UseCases\Member;

use App\Services\Smaregi\SmaregiApiService;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberWithdrawInput;
use App\Util\AdminContext;
use App\Util\LogHelper;

class MemberWithdrawUseCase
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
        private UserService $userService,
    ) {}

    public function handle(MemberWithdrawInput $input): void
    {
        $user = $input->user;
        $projectId = AdminContext::getCurrentProjectId() ?? (int) $user->project_id;

        $member = $user->member;
        if ($member !== null) {
            $smaregiCustomerId = $member->smaregi_customer_id ?? '';
            $contractId = $this->smaregiApi->getContractIdForProject($projectId);
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

        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $this->userService->releaseLineUserIdForWithdraw($user);

        $user->delete();

        LogHelper::info('[Member] user withdrew', [
            'project_id' => $projectId,
            'user_id' => $user->id,
        ]);
    }
}
