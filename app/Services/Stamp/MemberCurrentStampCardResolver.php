<?php

namespace App\Services\Stamp;

use App\Enums\StampCycleMode;
use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\StampCard;
use Illuminate\Support\Collection;

class MemberCurrentStampCardResolver
{
    public function resolve(Member $member): ?StampCard
    {
        $cards = $this->activeCards((int) $member->project_id);
        if ($cards->isEmpty()) {
            return null;
        }
        $memberStamps = $member->memberStamps()->get()->keyBy('stamp_card_id');
        $visible = $this->visibleCards($cards, $memberStamps);

        foreach ($visible as $card) {
            $existing = $memberStamps->get($card->id);
            if ($existing === null || StampCycleMode::isGrantClosed((string) $card->cycle_mode, $existing)) {
                continue;
            }

            return $card;
        }
        foreach ($visible as $card) {
            $existing = $memberStamps->get($card->id);
            if (StampCycleMode::isGrantClosed((string) $card->cycle_mode, $existing)) {
                continue;
            }

            return $card;
        }

        return null;
    }

    /** @return Collection<int, StampCard> */
    private function activeCards(int $projectId): Collection
    {
        $today = now()->startOfDay();

        return StampCard::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today);
            })
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, StampCard>  $cards
     * @param  Collection<int, MemberStamp>  $memberStamps
     * @return Collection<int, StampCard>
     */
    private function visibleCards(Collection $cards, Collection $memberStamps): Collection
    {
        $completedIds = [];
        foreach ($memberStamps as $stampCardId => $memberStamp) {
            if ($memberStamp->completed_at !== null) {
                $completedIds[(int) $stampCardId] = true;
            }
        }

        $visible = $cards->filter(function (StampCard $card) use ($cards, $completedIds) {
            $predecessors = $cards->filter(
                static fn (StampCard $candidate): bool => (int) ($candidate->next_stamp_card_id ?? 0) === (int) $card->id
            );
            if ($predecessors->isEmpty()) {
                return true;
            }
            foreach ($predecessors as $predecessor) {
                if (isset($completedIds[(int) $predecessor->id])) {
                    return true;
                }
            }

            return false;
        });

        return $visible->isEmpty() && $cards->isNotEmpty()
            ? $cards->values()
            : $visible->values();
    }
}
