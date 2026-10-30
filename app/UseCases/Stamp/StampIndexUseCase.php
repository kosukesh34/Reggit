<?php

namespace App\UseCases\Stamp;

use App\Domain\Settings\Repositories\StampSettings;
use App\Enums\StampCycleMode;
use App\Models\MemberStamp;
use App\Models\StampCard;
use App\Models\User;
use App\UseCases\Stamp\Data\StampCardView;
use App\UseCases\Stamp\Data\StampListView;
use App\Util\AdminContext;
use Illuminate\Support\Collection;

class StampIndexUseCase
{
    public function __construct(
        private StampSettings $shopSettingRepository,
    ) {}

    public function handle(User $user): StampListView
    {
        $member = $user->member;

        if ($member === null) {
            $projectId = AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null);
            $defaultImageUrl = $this->shopSettingRepository->getStampDefaultImageUrl($projectId);

            return new StampListView([], $defaultImageUrl, true);
        }

        $projectId = (int) $member->project_id;
        if ($this->shopSettingRepository->getUseStamps($projectId) !== true) {
            return new StampListView(
                [],
                $this->shopSettingRepository->getStampDefaultImageUrl($projectId),
                false,
            );
        }

        $today = now()->startOfDay();
        $cards = StampCard::query()
            ->where('project_id', $member->project_id)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today);
            })
            ->orderBy('name')
            ->get();

        $memberStamps = $member->memberStamps()->with('stampCard')->get()->keyBy('stamp_card_id');
        $defaultImageUrl = $this->shopSettingRepository->getStampDefaultImageUrl($member->project_id);
        $visibleCards = $this->visibleCards($cards, $memberStamps);

        $stampCards = $visibleCards->map(function (StampCard $card) use ($memberStamps, $cards) {
            return $this->toCardView($card, $memberStamps->get($card->id), $cards);
        })->sortBy(function (StampCardView $view) {
            return ($view->isArchived ? '1' : '0').$view->name;
        })->values()->all();

        return new StampListView($stampCards, $defaultImageUrl, false);
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
        if ($visible->isEmpty() && $cards->isNotEmpty()) {
            return $cards->values();
        }

        return $visible->values();
    }

    /**
     * @param  Collection<int, StampCard>  $cards
     */
    private function toCardView(StampCard $card, ?MemberStamp $memberStamp, Collection $cards): StampCardView
    {
        $mode = StampCycleMode::normalize($card->cycle_mode);
        $required = max(1, (int) $card->required_stamps);
        $current = $memberStamp !== null ? (int) $memberStamp->current_stamps : 0;
        $cycleCount = $memberStamp !== null ? (int) $memberStamp->cycle_count : 0;
        $completedAt = null;
        if ($memberStamp !== null && $memberStamp->completed_at !== null) {
            $completedAt = $memberStamp->completed_at->toIso8601String();
        }
        if ($mode === StampCycleMode::REPEAT && $current >= $required) {
            $cycleCount += intdiv($current, $required);
            $current = $current % $required;
            $completedAt = null;
        }
        $isArchived = $completedAt !== null && $mode !== StampCycleMode::REPEAT;
        $nextName = null;
        $nextId = (int) ($card->next_stamp_card_id ?? 0);
        if ($mode === StampCycleMode::NEXT_CARD && $nextId > 0) {
            $next = $cards->firstWhere('id', $nextId);
            $nextName = $next !== null ? (string) $next->name : null;
        }
        $memberStampValidUntil = $memberStamp !== null && $memberStamp->valid_until !== null
            ? $memberStamp->valid_until->toIso8601String()
            : null;
        $validUntil = $card->valid_until !== null ? $card->valid_until->format('Y-m-d') : null;
        $stampImages = is_array($card->stamp_images) ? $card->stamp_images : [];
        $stampEmptyImages = is_array($card->stamp_empty_images) ? $card->stamp_empty_images : [];

        return new StampCardView(
            (int) $card->id,
            (string) $card->name,
            $required,
            $card->reward_description,
            $this->normalizeRewardMilestones(is_array($card->reward_milestones) ? $card->reward_milestones : null),
            $card->image_url,
            $current,
            $completedAt,
            (string) ($card->layout_style ?? 'grid'),
            $card->valid_days !== null ? (int) $card->valid_days : null,
            $validUntil,
            $memberStampValidUntil,
            $stampImages,
            $stampEmptyImages,
            $mode,
            $cycleCount,
            $isArchived,
            $nextName,
            (string) ($card->design ?? StampCard::DESIGN_STANDARD),
        );
    }

    /**
     * @param  array<int|string, array<string, int|string|null>>|null  $milestones
     * @return array<int, array{stamp_count: int, description: string}>
     */
    private function normalizeRewardMilestones(?array $milestones): array
    {
        if ($milestones === null) {
            return [];
        }
        $normalized = [];
        foreach ($milestones as $milestone) {
            if (! is_array($milestone)) {
                continue;
            }
            $stampCount = (int) ($milestone['stamp_count'] ?? 0);
            $description = trim((string) ($milestone['description'] ?? ''));
            if ($stampCount <= 0 || $description === '') {
                continue;
            }
            $normalized[] = [
                'stamp_count' => $stampCount,
                'description' => $description,
            ];
        }
        usort(
            $normalized,
            static fn (array $a, array $b): int => (int) $a['stamp_count'] <=> (int) $b['stamp_count']
        );

        return $normalized;
    }
}
