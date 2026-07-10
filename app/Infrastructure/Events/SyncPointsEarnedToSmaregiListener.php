<?php

namespace App\Infrastructure\Events;

use App\Domain\Member\Events\PointsEarned;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;
use App\Services\Smaregi\SmaregiApiService;
use App\Util\LogHelper;

/**
 * ポイント増減時にスマレジへ残高差分を同期する。
 * 注文由来（orderId あり）・ベストエフォート指定は失敗時 warning、それ以外の手動付与は例外。
 * points は加算が正、取り消し・減算が負。
 */
class SyncPointsEarnedToSmaregiListener implements DomainEventListener
{
    public function __construct(
        private MemberRepository $memberRepository,
        private SmaregiApiService $smaregiApi,
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof PointsEarned === false) {
            return;
        }

        if ($event->points === 0) {
            return;
        }

        $member = $this->memberRepository->findById($event->memberId, $event->projectId);
        if ($member === null) {
            return;
        }

        $smaregiCustomerId = $member->smaregiCustomerId();
        if ($smaregiCustomerId === '') {
            return;
        }

        $result = $this->smaregiApi->syncCustomerPointsForProject(
            $member->projectId(),
            $smaregiCustomerId,
            $event->points,
        );

        if ($result !== null) {
            return;
        }

        if ($event->orderId !== null || $event->isBestEffortSync) {
            LogHelper::warning('[DomainEvent] smaregi points sync failed on point delta', [
                'member_id' => $event->memberId,
                'project_id' => $event->projectId,
                'order_id' => $event->orderId,
                'points' => $event->points,
                'is_best_effort' => $event->isBestEffortSync,
            ]);

            return;
        }

        throw new \Exception('スマレジへのポイント反映に失敗しました。storage/logs/laravel.log の [Smaregi] を参照してください。');
    }
}
