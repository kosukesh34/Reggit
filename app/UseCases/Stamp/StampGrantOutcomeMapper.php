<?php

namespace App\UseCases\Stamp;

use App\Enums\StampCycleMode;
use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\StampCard;
use App\UseCases\Stamp\Data\StampGrantOutput;

class StampGrantOutcomeMapper
{
    public function closed(StampCard $card): StampGrantOutput
    {
        $next = $card->activeNextCard();
        $message = $next !== null
            ? 'このカードは達成済みです。次は「'.$next->name.'」です'
            : 'このカードは達成済みです';

        return new StampGrantOutput(false, 409, $message);
    }

    public function success(StampCard $card, MemberStamp $memberStamp, int $amount, ?MemberStamp $before): StampGrantOutput
    {
        $wasCompleted = $before !== null && $before->completed_at !== null;
        $completedNow = $memberStamp->cyclesCompletedNow > 0
            || ($memberStamp->completed_at !== null && $wasCompleted === false);
        $next = $completedNow === true ? $card->activeNextCard() : null;
        $cycleCount = (int) $memberStamp->cycle_count;
        $nextName = $next !== null ? (string) $next->name : null;

        return new StampGrantOutput(
            true,
            200,
            StampCycleMode::grantSuccessMessage(
                (string) $card->cycle_mode,
                $amount,
                $completedNow,
                $cycleCount,
                $nextName,
            ),
            (int) $card->id,
            (int) $memberStamp->current_stamps,
            $amount,
            $completedNow,
            $cycleCount,
            $next !== null ? (int) $next->id : null,
            $nextName,
        );
    }

    public function existingStamp(Member $member, StampCard $card): ?MemberStamp
    {
        return MemberStamp::query()
            ->where('member_id', $member->id)
            ->where('stamp_card_id', $card->id)
            ->first();
    }
}
