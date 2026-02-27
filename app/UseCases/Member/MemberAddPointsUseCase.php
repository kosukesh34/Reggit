<?php

namespace App\UseCases\Member;

use App\Models\ShopSetting;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberPointsOutput;
use App\UseCases\Smaregi\SmaregiSyncCustomerPointsUseCase;
use Illuminate\Http\Request;

class MemberAddPointsUseCase
{
    public function __construct(
        private UserService $userService,
        private SmaregiSyncCustomerPointsUseCase $smaregiSyncPoints,
    ) {
    }

    public function handle(Request $request, int $points, ?string $description): MemberPointsOutput
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            return new MemberPointsOutput(false, 0, collect());
        }

        $projectId = $member->project_id ?? 1;
        $contractId = ShopSetting::getValue('smaregi_contract_id', null, $projectId);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        $smaregiCustomerId = $member->smaregi_customer_id ?? '';

        if ($contractId !== '' && $smaregiCustomerId !== '' && $points > 0) {
            $result = $this->smaregiSyncPoints->execute($projectId, $smaregiCustomerId, $points);
            if ($result === null) {
                throw new \Exception('スマレジへのポイント反映に失敗しました。storage/logs/laravel.log の [Smaregi] を参照してください。');
            }
        }

        $member->addPoints($points, $description);

        return new MemberPointsOutput(true, (int) $member->fresh()->points, collect());
    }
}
