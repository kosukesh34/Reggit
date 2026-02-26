<?php

namespace App\UseCases\Stamp;

use App\Models\ShopSetting;
use App\Models\StampCard;
use App\Services\UserService;
use App\UseCases\Stamp\Data\StampCardView;
use App\UseCases\Stamp\Data\StampListView;
use Illuminate\Http\Request;

class StampIndexUseCase
{
    public function __construct(private UserService $userService)
    {
    }

    public function handle(Request $request): StampListView
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            return new StampListView([], null, true);
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
        $defaultImageUrl = ShopSetting::getStampDefaultImageUrl($member->project_id);

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
}
