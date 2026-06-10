<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\UseCases\Coupon\CouponMyCouponsUseCase;
use App\UseCases\Member\Data\MemberUserInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 会員クーポン一覧。責務は UseCase に委譲する。
 */
class CouponController extends Controller
{
    public function __construct(
        private CouponMyCouponsUseCase $myCouponsUseCase,
        private UserService $userService,
    ) {}

    public function myCoupons(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $result = $this->myCouponsUseCase->handle(new MemberUserInput($user));

            if ($result->requiresMembership) {
                return response()->json([
                    'coupons' => [],
                    'message' => '会員登録が必要です',
                    'default_image_url' => $result->defaultImageUrl,
                ]);
            }

            return response()->json([
                'coupons' => $result->couponsToArray(),
                'default_image_url' => $result->defaultImageUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('My coupons error: '.$e->getMessage());

            return response()->json(['error' => 'クーポンの取得に失敗しました'], 500);
        }
    }
}
