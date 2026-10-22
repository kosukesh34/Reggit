<?php

namespace App\Http\Controllers;

use App\Services\Subscription\MemberSubscriptionService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MemberSubscriptionController extends Controller
{
    public function __construct(
        private UserService $userService,
        private MemberSubscriptionService $subscriptions,
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate(['product_id' => ['required', 'integer']]);

        return $this->respond(fn (): array => [
            'checkout_url' => $this->subscriptions->startCheckout($this->userService->getOrCreateUser($request), (int) $validated['product_id']),
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        return $this->respond(function () use ($request, $id): array {
            $subscription = $this->subscriptions->cancelForMember($this->userService->getOrCreateUser($request), $id);

            return ['id' => $subscription->id, 'cancel_at_period_end' => $subscription->cancel_at_period_end];
        });
    }

    /** @param callable(): array<string, mixed> $action */
    private function respond(callable $action): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $action()]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'サブスクを処理できません',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
