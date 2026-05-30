<?php

namespace App\UseCases\Member;

use App\Services\Smaregi\SmaregiApiService;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberPointsOutput;
use Illuminate\Http\Request;

class MemberAddPointsUseCase
{
    public function __construct(
        private UserService $userService,
        private SmaregiApiService $smaregiApi,
    ) {}

    public function handle(Request $request, int $points, ?string $description): MemberPointsOutput
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            return new MemberPointsOutput(false, 0, collect());
        }

        $projectId = $member->project_id ?? 1;
        $smaregiCustomerId = $member->smaregi_customer_id ?? '';
        $smaregiCustomerId = is_string($smaregiCustomerId) ? trim($smaregiCustomerId) : '';

        $member->syncPointsLedger();
        $member->addPoints($points, $description);

        if ($smaregiCustomerId !== '' && $points > 0) {
            $result = $this->smaregiApi->syncCustomerPointsForProject($projectId, $smaregiCustomerId, $points);
            if ($result === null) {
                throw new \Exception('スマレジへのポイント反映に失敗しました。storage/logs/laravel.log の [Smaregi] を参照してください。');
            }
        }

        return new MemberPointsOutput(true, (int) $member->fresh()->getDisplayPoints(), collect());
    }
}
