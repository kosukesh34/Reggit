<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\DeviceOnceCouponService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeviceOnceCouponController extends BaseApiController
{
    public function __construct(
        private UserService $userService,
        private DeviceOnceCouponService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getOrCreateUser($request);

        return $this->success([
            'coupons' => $this->service->available($user, $this->deviceIdentifier($request)),
        ]);
    }

    public function claim(Request $request): JsonResponse
    {
        $coupon = (int) $request->route('coupon');
        try {
            $issued = $this->service->claimById(
                $this->userService->getOrCreateUser($request),
                $coupon,
                $this->deviceIdentifier($request),
            );

            return $this->success([
                'member_coupon_id' => (int) $issued->id,
                'coupon_id' => (int) $issued->coupon_id,
            ], 'クーポンを獲得しました');
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'クーポンを獲得できません';

            return $this->error($message, 422, [], $e->errors());
        }
    }

    private function deviceIdentifier(Request $request): string
    {
        return trim((string) ($request->header('X-Device-Identifier') ?? $request->input('device_identifier', '')));
    }
}
