<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\UseCases\Coupon\CouponMyCouponsUseCase;
use App\UseCases\Coupon\CouponUseMemberCouponUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * 会員クーポン一覧。責務は UseCase に委譲する。
 */
class CouponController extends Controller
{
    public function __construct(
        private CouponMyCouponsUseCase $myCouponsUseCase,
        private CouponUseMemberCouponUseCase $useMemberCouponUseCase,
        private UserService $userService,
    ) {}

    public function myCoupons(Request $request)
    {
        try {
            $result = $this->myCouponsUseCase->handle($this->userService->getOrCreateUser($request));

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

    public function use(Request $request, int $id)
    {
        try {
            $result = $this->useMemberCouponUseCase->handle($this->userService->getOrCreateUser($request), $id);

            return response()->json([
                'success' => true,
                'data' => $result->toArray(),
            ]);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'クーポンを使用できません';

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Use coupon error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'クーポンの使用に失敗しました',
            ], 500);
        }
    }
}
