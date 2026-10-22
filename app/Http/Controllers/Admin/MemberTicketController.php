<?php

namespace App\Http\Controllers\Admin;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\MemberTicket;
use App\Services\Subscription\MemberSubscriptionService;
use App\Services\Ticket\MemberTicketService;
use Illuminate\Http\RedirectResponse;

class MemberTicketController extends AdminBaseController
{
    public function __construct(private MemberTicketService $tickets) {}

    public function cancelUse(string $project_key, int $id, int $ticket, int $use): RedirectResponse
    {
        $projectId = $this->resolveAdminProjectId();
        $this->tickets->cancelUse($projectId, $this->visibleTicket($id, $ticket)->id, $use);

        return $this->back($project_key, $id, '回数券の利用を1回分取り消しました');
    }

    public function revoke(string $project_key, int $id, int $ticket): RedirectResponse
    {
        $projectId = $this->resolveAdminProjectId();
        $this->tickets->revoke($projectId, $this->visibleTicket($id, $ticket)->id);

        return $this->back($project_key, $id, '回数券を無効にしました');
    }

    public function cancelSubscription(string $project_key, int $id, int $subscription, MemberSubscriptionService $subscriptions): RedirectResponse
    {
        $member = Member::query()->findOrFail($id);
        $target = MemberSubscription::query()->where('member_id', $member->id)->findOrFail($subscription);
        try {
            $subscriptions->cancel($target);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.members.detail', ['project_key' => $project_key, 'id' => $id])
                ->with('error', 'サブスクを解約できませんでした。Stripe の管理画面で確認してください');
        }

        return $this->back($project_key, $id, 'サブスクを現在の期間の終わりで解約します');
    }

    /** 店舗スタッフの閲覧範囲にいる会員の回数券だけを操作できる */
    private function visibleTicket(int $memberId, int $ticketId): MemberTicket
    {
        $member = Member::query()->findOrFail($memberId);

        return MemberTicket::query()->where('member_id', $member->id)->findOrFail($ticketId);
    }

    private function back(string $projectKey, int $memberId, string $message): RedirectResponse
    {
        return redirect()->route('admin.members.detail', ['project_key' => $projectKey, 'id' => $memberId])
            ->with('success', $message);
    }
}
