<?php

namespace App\Services\Smaregi;

use App\Domain\Member\Repositories\MemberRepository;
use App\Util\LogHelper;

/**
 * 会員ポイント差分をスマレジへ同期する。
 * points は加算が正、取り消し・減算が負。
 */
class SmaregiPointsSyncService
{
    public function __construct(
        private MemberRepository $memberRepository,
        private SmaregiApiService $smaregiApi,
    ) {}

    /**
     * @return bool 同期成功またはスキップ（顧客未連携等）なら true。API 失敗なら false
     */
    public function syncPointDelta(
        int $memberId,
        int $projectId,
        int $points,
        ?int $orderId,
        bool $isBestEffortSync = false,
    ): bool {
        if ($points === 0) {
            return true;
        }

        $member = $this->memberRepository->findById($memberId, $projectId);
        if ($member === null) {
            return true;
        }

        $smaregiCustomerId = $member->smaregiCustomerId();
        if ($smaregiCustomerId === '') {
            return true;
        }

        $result = $this->smaregiApi->syncCustomerPointsForProject(
            $member->projectId(),
            $smaregiCustomerId,
            $points,
        );

        if ($result !== null) {
            return true;
        }

        if ($orderId !== null || $isBestEffortSync) {
            LogHelper::warning('[DomainEvent] smaregi points sync failed on point delta', [
                'member_id' => $memberId,
                'project_id' => $projectId,
                'order_id' => $orderId,
                'points' => $points,
                'is_best_effort' => $isBestEffortSync,
            ]);

            return false;
        }

        throw new \Exception('スマレジへのポイント反映に失敗しました。storage/logs/laravel.log の [Smaregi] を参照してください。');
    }
}
