<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Member\AdminMemberTicketUseCase;
use Illuminate\Http\RedirectResponse;

class MemberTicketController extends AdminBaseController
{
    public function __construct(private AdminMemberTicketUseCase $tickets) {}

    public function cancelUse(string $project_key, int $id, int $ticket, int $use): RedirectResponse
    {
        $this->tickets->cancelUse($this->resolveAdminProjectId(), $id, $ticket, $use);

        return $this->back($project_key, $id, '回数券の利用を1回分取り消しました');
    }

    public function revoke(string $project_key, int $id, int $ticket): RedirectResponse
    {
        $this->tickets->revoke($this->resolveAdminProjectId(), $id, $ticket);

        return $this->back($project_key, $id, '回数券を無効にしました');
    }

    public function cancelSubscription(string $project_key, int $id, int $subscription): RedirectResponse
    {
        if (! $this->tickets->cancelSubscription($id, $subscription)) {
            return redirect()->route('admin.members.detail', ['project_key' => $project_key, 'id' => $id])
                ->with('error', 'サブスクを解約できませんでした。Stripe の管理画面で確認してください');
        }

        return $this->back($project_key, $id, 'サブスクを現在の期間の終わりで解約します');
    }

    private function back(string $projectKey, int $memberId, string $message): RedirectResponse
    {
        return redirect()->route('admin.members.detail', ['project_key' => $projectKey, 'id' => $memberId])
            ->with('success', $message);
    }
}
