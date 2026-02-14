<?php

namespace App\UseCases\Stamp;

use App\Models\ShopSetting;
use App\Models\StampCard;
use App\Services\UserService;
use App\UseCases\Stamp\Dto\StampCardDto;
use App\UseCases\Stamp\Dto\StampListDto;
use Illuminate\Http\Request;

class StampIndexUseCase
{
    public function __construct(private UserService $userService)
    {
    }

    public function handle(Request $request): StampListDto
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            return new StampListDto([], null, true);
        }

        $cards = StampCard::where('project_id', $member->project_id)
            ->where('is_active', true)
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

            return new StampCardDto(
                (int) $card->id,
                (string) $card->name,
                (int) $card->required_stamps,
                $card->reward_description,
                $card->image_url,
                $memberStamp ? (int) $memberStamp->current_stamps : 0,
                $completedAt
            );
        })->all();

        return new StampListDto($stampCards, $defaultImageUrl, false);
    }
}
