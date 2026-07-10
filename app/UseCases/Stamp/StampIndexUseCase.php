<?php

namespace App\UseCases\Stamp;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\StampCard;
use App\Models\User;
use App\UseCases\Stamp\Data\StampCardView;
use App\UseCases\Stamp\Data\StampListView;

class StampIndexUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(User $user): StampListView
    {
        $member = $user->member;

        if ($member === null) {
            $projectId = $user->project_id ?? 1;
            $defaultImageUrl = $this->shopSettingRepository->getStampDefaultImageUrl($projectId);

            return new StampListView([], $defaultImageUrl, true);
        }

        $today = now()->startOfDay();
        $cards = StampCard::where('project_id', $member->project_id)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today);
            })
            ->orderBy('name')
            ->get();

        $memberStamps = $member->memberStamps()->with('stampCard')->get()->keyBy('stamp_card_id');
        $defaultImageUrl = $this->shopSettingRepository->getStampDefaultImageUrl($member->project_id);

        $stampCards = $cards->map(function ($card) use ($memberStamps) {
            $memberStamp = $memberStamps->get($card->id);

            $completedAt = null;
            if ($memberStamp !== null && $memberStamp->completed_at !== null) {
                $completedAt = $memberStamp->completed_at->toIso8601String();
            }
            $memberStampValidUntil = $memberStamp && $memberStamp->valid_until
                ? $memberStamp->valid_until->toIso8601String()
                : null;
            $validUntil = $card->valid_until ? $card->valid_until->format('Y-m-d') : null;

            return new StampCardView(
                (int) $card->id,
                (string) $card->name,
                (int) $card->required_stamps,
                $card->reward_description,
                $this->normalizeRewardMilestones($card->reward_milestones),
                $card->image_url,
                $memberStamp ? (int) $memberStamp->current_stamps : 0,
                $completedAt,
                (string) ($card->layout_style ?? 'grid'),
                $card->valid_days ? (int) $card->valid_days : null,
                $validUntil,
                $memberStampValidUntil
            );
        })->all();

        return new StampListView($stampCards, $defaultImageUrl, false);
    }

    /**
     * @return array<int, array{stamp_count: int, description: string}>
     */
    private function normalizeRewardMilestones(mixed $milestones): array
    {
        if (! is_array($milestones)) {
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
