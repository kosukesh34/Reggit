<?php

namespace App\UseCases\Admin\Member;

use App\Models\Member;
use App\Services\Admin\MemberAdminService;
use App\Services\Smaregi\SmaregiApiService;
use App\UseCases\Admin\Member\Data\AdminMemberUpdateInput;
use App\Util\LogHelper;

class AdminMemberUpdateUseCase
{
    private const DEFAULT_POINT_DESCRIPTION = '管理画面での調整';

    public function __construct(
        private MemberAdminService $memberAdminService,
        private SmaregiApiService $smaregiApiService,
    ) {}

    public function handle(AdminMemberUpdateInput $input): void
    {
        $member = $this->memberAdminService->findForWrite($input->memberId, $input->projectId);

        $this->memberAdminService->updateMemberProfile($member, $input->userData, $input->memberData);

        $member = $member->fresh();
        if ($member === null) {
            return;
        }

        $member->syncPointsLedger();
        $member->refresh();

        $adjustment = $input->pointAdjustment;
        if ($adjustment === null) {
            return;
        }

        $desc = isset($adjustment['description']) && (string) $adjustment['description'] !== ''
            ? (string) $adjustment['description']
            : self::DEFAULT_POINT_DESCRIPTION;

        $delta = $this->applyPointAdjustment($member, $adjustment, $desc);
        $member = $member->fresh();
        if ($member === null) {
            return;
        }

        if ($delta !== 0) {
            $this->syncPointDeltaToSmaregi($member, $delta);
        }
    }

    /** @param array{type: 'add'|'subtract'|'set', points: int, description?: string|null} $adjustment */
    private function applyPointAdjustment(Member $member, array $adjustment, string $description): int
    {
        $member->syncPointsLedger();
        $member->refresh();
        $before = $member->getDisplayPoints();

        if ($adjustment['type'] === 'set') {
            $target = max(0, (int) $adjustment['points']);
            $delta = $target - $before;
            if ($delta > 0) {
                $member->addPoints($delta, $description, null);
            } elseif ($delta < 0) {
                $member->usePoints(abs($delta), $description, null);
            }
        } elseif ($adjustment['type'] === 'add') {
            $points = (int) $adjustment['points'];
            if ($points > 0) {
                $member->addPoints($points, $description, null);
            }
        } elseif ($adjustment['type'] === 'subtract') {
            $points = min((int) $adjustment['points'], $before);
            if ($points > 0) {
                $member->usePoints($points, $description, null);
            }
        }

        $member->refresh();

        return $member->getTotalPointsFromTransactions() - $before;
    }

    private function syncPointDeltaToSmaregi(Member $member, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $smaregiCustomerId = trim((string) ($member->smaregi_customer_id ?? ''));
        if ($smaregiCustomerId === '') {
            return;
        }

        $result = $this->smaregiApiService->syncCustomerPointsForProject(
            (int) $member->project_id,
            $smaregiCustomerId,
            $delta
        );

        if ($result === null) {
            LogHelper::warning('[AdminMember] smaregi point sync failed after local adjustment', [
                'member_id' => (int) $member->id,
                'project_id' => (int) $member->project_id,
                'smaregi_customer_id' => $smaregiCustomerId,
                'point_delta' => $delta,
            ]);
        }
    }
}
