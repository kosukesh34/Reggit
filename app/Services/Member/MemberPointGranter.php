<?php

namespace App\Services\Member;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Settings\Repositories\OrderSettings;
use Carbon\CarbonImmutable;

/** 特典ポイントを有効期限付きで付与する。呼び出し側のトランザクション内で使う。 */
class MemberPointGranter
{
    public function __construct(
        private readonly MemberRepository $members,
        private readonly OrderSettings $settings,
    ) {}

    /** @return int 付与したポイント。ポイント機能が無効なら 0 */
    public function grant(int $projectId, int $memberId, int $points, string $description): int
    {
        if ($points <= 0 || ! $this->settings->getUsePoints($projectId)) {
            return 0;
        }
        $this->members->syncPointsLedger($memberId, $projectId);
        $member = $this->members->findByIdForUpdate($memberId, $projectId);
        if ($member === null) {
            throw new \RuntimeException('Member not found for point grant: '.$memberId);
        }
        $member->earnPoints($points, $description, null, CarbonImmutable::now()->addDays($this->settings->getPointsValidDays($projectId)));
        $this->members->save($member);

        return $points;
    }
}
