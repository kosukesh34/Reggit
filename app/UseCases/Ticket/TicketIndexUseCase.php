<?php

namespace App\UseCases\Ticket;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\MemberTicket;
use App\Models\Product;

/** 回数券・サブスクの購入候補と、会員が持っている回数券・サブスク。 */
class TicketIndexUseCase
{
    public function __construct(private FeatureSettings $features) {}

    /** @return array{plans: list<array<string, mixed>>, tickets: list<array<string, mixed>>, subscriptions: list<array<string, mixed>>, requires_membership: bool} */
    public function handle(int $projectId, ?Member $member): array
    {
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
            'daily_limit' => $product->daily_limit,
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
            ->map(fn (MemberTicket $ticket): array => $this->present($ticket))
            ->all();

        return [
            'plans' => $plans,
            'tickets' => $tickets,
            'subscriptions' => $subscriptions,
            'requires_membership' => $member === null,
        ];
    }

    /** @return array<string, mixed> */
    public function present(MemberTicket $ticket): array
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
