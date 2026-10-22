<?php

namespace App\Http\Controllers;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Models\MemberSubscription;
use App\Models\MemberTicket;
use App\Models\Product;
use App\Services\Ticket\MemberTicketService;
use App\Services\UserService;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * 回数券の購入候補・保有一覧と利用。購入は通常の注文 API（order_type=ticket）を使う。
 */
class TicketController extends Controller
{
    public function __construct(
        private UserService $userService,
        private MemberTicketService $tickets,
        private FeatureSettings $features,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $member = $this->userService->getOrCreateUser($request)->member;
        $onSale = $this->features->isFeatureEnabled('feature_tickets_enabled', $projectId)
            ? Product::query()->where(fn ($query) => $query->whereNotNull('ticket_uses')->orWhere('subscription_enabled', true))
                ->where('is_available', true)->orderBy('price')->get()
            : collect();
        $plans = $onSale->map(fn (Product $product): array => [
            'id' => $product->id,
            'kind' => $product->subscription_enabled ? 'subscription' : 'ticket',
            'name' => $product->name,
            'description' => $product->description,
            'price' => (int) $product->price,
            'image_url' => $product->image_url,
            'ticket_uses' => $product->subscription_enabled ? $product->subscription_monthly_uses : $product->ticket_uses,
            'ticket_valid_days' => $product->ticket_valid_days,
            'daily_limit' => $product->subscription_daily_limit,
        ])->values()->all();
        $subscriptions = $member === null ? [] : MemberSubscription::query()
            ->where('member_id', $member->id)
            ->whereNotIn('status', MemberSubscription::ENDED_STATUSES)
            ->latest('id')
            ->get()
            ->map(fn (MemberSubscription $subscription): array => [
                'id' => $subscription->id,
                'product_id' => $subscription->product_id,
                'name' => $subscription->name,
                'status' => $subscription->status,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'current_period_end' => $subscription->current_period_end?->toIso8601String(),
            ])->all();
        $tickets = $member === null ? [] : MemberTicket::query()
            ->where('member_id', $member->id)
            ->latest('id')
            ->get()
            ->map(fn (MemberTicket $ticket): array => $this->ticketArray($ticket))
            ->all();

        return response()->json([
            'plans' => $plans,
            'tickets' => $tickets,
            'subscriptions' => $subscriptions,
            'requires_membership' => $member === null,
        ]);
    }

    public function use(Request $request, int $id): JsonResponse
    {
        try {
            $ticket = $this->tickets->useOne($this->userService->getOrCreateUser($request), $id);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? '回数券を使用できません',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true, 'data' => $this->ticketArray($ticket)]);
    }

    /** @return array<string, mixed> */
    private function ticketArray(MemberTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'name' => $ticket->name,
            'total_uses' => $ticket->total_uses,
            'used_count' => $ticket->used_count,
            'daily_limit' => $ticket->daily_limit,
            'is_subscription' => $ticket->member_subscription_id !== null,
            'remaining_uses' => $ticket->remainingUses(),
            'expires_at' => $ticket->expires_at?->toIso8601String(),
            'status' => $ticket->status(),
            'purchased_at' => $ticket->created_at?->toIso8601String(),
        ];
    }
}
