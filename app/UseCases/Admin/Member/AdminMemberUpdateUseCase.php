<?php

namespace App\UseCases\Admin\Member;

use App\Domain\Member\Events\PointsEarned;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Settings\Repositories\RankSettings;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\Member\Data\AdminMemberUpdateInput;
use Illuminate\Support\Facades\DB;

class AdminMemberUpdateUseCase
{
    private const DEFAULT_POINT_DESCRIPTION = '管理画面での調整';

    public function __construct(
        private MemberAdminService $memberAdminService,
        private MemberRepository $memberRepository,
        private RankSettings $shopSettingRepository,
        private DomainEventDispatcher $domainEvents,
    ) {}

    public function handle(AdminMemberUpdateInput $input): void
    {
        $member = $this->memberAdminService->findForWrite($input->memberId, $input->projectId);

        $this->memberAdminService->updateMemberProfile($member, $input->userData, $input->memberData, $input->profileExtraIsPatch);

        $this->memberRepository->syncPointsLedger($input->memberId, $input->projectId);

        $adjustment = $input->pointAdjustment;
        if ($adjustment === null) {
            return;
        }

        $desc = isset($adjustment['description']) && (string) $adjustment['description'] !== ''
            ? (string) $adjustment['description']
            : self::DEFAULT_POINT_DESCRIPTION;

        $delta = DB::transaction(function () use ($input, $adjustment, $desc) {
            return $this->applyPointAdjustment($input->memberId, $input->projectId, $adjustment, $desc);
        });

        if ($delta !== 0) {
            $this->domainEvents->dispatch(new PointsEarned(
                $input->memberId,
                $input->projectId,
                $delta,
                null,
                isBestEffortSync: true,
            ));
        }
    }

    /**
     * @param  array{type: 'add'|'subtract'|'set', points: int, description?: string|null}  $adjustment
     */
    private function applyPointAdjustment(int $memberId, int $projectId, array $adjustment, string $description): int
    {
        $this->memberRepository->syncPointsLedger($memberId, $projectId);
        $member = $this->memberRepository->findByIdForUpdate($memberId, $projectId);
        if ($member === null) {
            return 0;
        }

        $before = $member->displayPoints();
        $validDays = $this->shopSettingRepository->getPointsValidDays($projectId);
        $expiresAt = now()->addDays($validDays);

        if ($adjustment['type'] === 'set') {
            $target = max(0, (int) $adjustment['points']);
            $delta = $target - $before;
            if ($delta > 0) {
                $member->earnPoints($delta, $description, null, $expiresAt);
            } elseif ($delta < 0) {
                $member->usePoints(abs($delta), $description, null);
            }
        } elseif ($adjustment['type'] === 'add') {
            $points = (int) $adjustment['points'];
            if ($points > 0) {
                $member->earnPoints($points, $description, null, $expiresAt);
            }
        } elseif ($adjustment['type'] === 'subtract') {
            $points = min((int) $adjustment['points'], $before);
            if ($points > 0) {
                $member->usePoints($points, $description, null);
            }
        }

        $this->memberRepository->save($member);

        return $member->displayPoints() - $before;
    }
}
