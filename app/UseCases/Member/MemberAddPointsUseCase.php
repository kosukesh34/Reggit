<?php

namespace App\UseCases\Member;

use App\Services\Smaregi\SmaregiApiService;
use App\UseCases\Member\Data\MemberAddPointsInput;
use App\UseCases\Member\Data\MemberPointsOutput;

class MemberAddPointsUseCase
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
    ) {}

    public function handle(MemberAddPointsInput $input): MemberPointsOutput
    {
        $member = $input->user->member;

        if ($member === null) {
            return new MemberPointsOutput(false, 0, collect());
        }

        $projectId = $member->project_id ?? 1;
        $smaregiCustomerId = $member->smaregi_customer_id ?? '';
        $smaregiCustomerId = is_string($smaregiCustomerId) ? trim($smaregiCustomerId) : '';

        $member->syncPointsLedger();
        $member->addPoints($input->points, $input->description);

        if ($smaregiCustomerId !== '' && $input->points > 0) {
            $result = $this->smaregiApi->syncCustomerPointsForProject($projectId, $smaregiCustomerId, $input->points);
            if ($result === null) {
                throw new \Exception('スマレジへのポイント反映に失敗しました。storage/logs/laravel.log の [Smaregi] を参照してください。');
            }
        }

        return new MemberPointsOutput(true, (int) $member->fresh()->getDisplayPoints(), collect());
    }
}
