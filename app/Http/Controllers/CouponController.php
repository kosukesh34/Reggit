<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{
    public function __construct(private UserService $userService)
    {
    }

    public function myCoupons(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $member = $user->member;
            if (!$member) {
                return response()->json([
                    'coupons' => [],
                    'message' => '会員登録が必要です',
                ]);
            }

            $coupons = $member->memberCoupons()
                ->with('coupon')
                ->orderBy('issued_at', 'desc')
                ->get()
                ->map(function ($mc) {
                    return [
                        'id' => $mc->id,
                        'coupon_name' => $mc->coupon_name_snapshot ?? $mc->coupon?->name,
                        'image_url' => $mc->coupon?->image_url,
                        'status' => $mc->status,
                        'issued_at' => $mc->issued_at?->toIso8601String(),
                        'valid_from' => $mc->valid_from?->toIso8601String(),
                        'valid_until' => $mc->valid_until?->toIso8601String(),
                        'used_at' => $mc->used_at?->toIso8601String(),
                    ];
                });

            return response()->json([
                'coupons' => $coupons,
                'default_image_url' => \App\Models\ShopSetting::getCouponDefaultImageUrl($member->project_id),
            ]);
        } catch (\Exception $e) {
            Log::error('My coupons error: ' . $e->getMessage());
            return response()->json(['error' => 'クーポンの取得に失敗しました'], 500);
        }
    }
}
