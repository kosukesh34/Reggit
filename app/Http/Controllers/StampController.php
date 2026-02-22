<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StampController extends Controller
{
    public function __construct(private UserService $userService)
    {
    }

    public function index(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $member = $user->member;
            if ($member === null) {
                return response()->json([
                    'stamp_cards' => [],
                    'message' => '会員登録が必要です',
                ]);
            }

            $cards = \App\Models\StampCard::where('project_id', $member->project_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $memberStamps = $member->memberStamps()->with('stampCard')->get()->keyBy('stamp_card_id');
            $defaultImageUrl = \App\Models\ShopSetting::getStampDefaultImageUrl($member->project_id);

            $stampCards = $cards->map(function ($card) use ($memberStamps) {
                $ms = $memberStamps->get($card->id);
                return [
                    'id' => $card->id,
                    'name' => $card->name,
                    'required_stamps' => $card->required_stamps,
                    'reward_description' => $card->reward_description,
                    'image_url' => $card->image_url,
                    'current_stamps' => $ms ? $ms->current_stamps : 0,
                    'completed_at' => $ms && $ms->completed_at ? $ms->completed_at->toIso8601String() : null,
                ];
            });

            return response()->json([
                'stamp_cards' => $stampCards,
                'default_image_url' => $defaultImageUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Stamps index error: ' . $e->getMessage());
            return response()->json(['error' => 'スタンプの取得に失敗しました'], 500);
        }
    }
}
