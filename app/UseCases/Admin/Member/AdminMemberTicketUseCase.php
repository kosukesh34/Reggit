<?php

namespace App\UseCases\Admin\Member;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\MemberTicket;
use App\Services\Subscription\MemberSubscriptionService;
use App\Services\Ticket\MemberTicketService;
use Illuminate\Support\Collection;

/** 会員詳細からの回数券・サブスク操作。店舗スタッフの閲覧範囲（StoreAccessScope）にいる会員の分だけ探す。 */
class AdminMemberTicketUseCase
{
    public function __construct(
        private MemberTicketService $tickets,
        private MemberSubscriptionService $subscriptions,
    ) {}

    public function cancelUse(int $projectId, int $memberId, int $ticketId, int $useId): void
    {
        $this->tickets->cancelUse($projectId, $this->visibleTicket($memberId, $ticketId)->id, $useId);
    }

    public function revoke(int $projectId, int $memberId, int $ticketId): void
    {
        $this->tickets->revoke($projectId, $this->visibleTicket($memberId, $ticketId)->id);
    }

    /** @return bool Stripe 側で解約できなかった場合は false（例外は報告済み） */
    public function cancelSubscription(int $memberId, int $subscriptionId): bool
    {
        $member = Member::query()->findOrFail($memberId);
        $target = MemberSubscription::query()->where('member_id', $member->id)->findOrFail($subscriptionId);
        try {
            $this->subscriptions->cancel($target);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }

    /** @return array{tickets: Collection<int, MemberTicket>, subscriptions: Collection<int, MemberSubscription>} 会員詳細に出す回数券・サブスク（新しい順） */
    public function forMember(int $memberId): array
    {
        return [
            'tickets' => MemberTicket::query()->with('uses.store')->where('member_id', $memberId)->latest('id')->get(),
            'subscriptions' => MemberSubscription::query()->where('member_id', $memberId)->latest('id')->get(),
        ];
    }

    private function visibleTicket(int $memberId, int $ticketId): MemberTicket
    {
        $member = Member::query()->findOrFail($memberId);

        return MemberTicket::query()->where('member_id', $member->id)->findOrFail($ticketId);
    }
}
