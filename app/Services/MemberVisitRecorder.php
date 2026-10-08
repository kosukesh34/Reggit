<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberVisit;
use App\Util\DateUtil;

class MemberVisitRecorder
{
    public function record(
        Member $member,
        \DateTimeInterface $visitedAt,
        string $source,
        ?int $orderId = null,
        ?string $note = null,
        ?float $amount = null,
        ?string $smaregiTransactionHeadId = null,
    ): MemberVisit {
        $visitedCarbon = DateUtil::nullableCarbon($visitedAt);
        if ($visitedCarbon === null) {
            throw new \InvalidArgumentException('来店日時が不正です');
        }
        $visit = MemberVisit::create([
            'project_id' => $member->project_id,
            'member_id' => $member->id,
            'visited_at' => $visitedCarbon,
            'source' => $source,
            'order_id' => $orderId,
            'note' => $note,
            'amount' => $amount,
            'smaregi_transaction_head_id' => $smaregiTransactionHeadId,
        ]);

        $currentLast = DateUtil::nullableCarbon($member->getRawOriginal('last_visited_at'));
        if ($currentLast === null || $visitedCarbon->gt($currentLast)) {
            $member->update(['last_visited_at' => $visitedCarbon]);
            $member->refresh();
        }

        return $visit;
    }
}
