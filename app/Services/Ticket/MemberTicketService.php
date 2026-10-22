<?php

namespace App\Services\Ticket;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\MemberTicket;
use App\Models\MemberTicketUse;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Member\CurrentMemberPrincipal;
use App\Util\StoreContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberTicketService
{
    public function __construct(
        private CurrentMemberPrincipal $principals,
        private StoreContext $storeContext,
    ) {}

    /** 決済確定した回数券注文の明細ごとに回数券を発行する（冪等）。 */
    public function issueForPaidOrder(Order $order): void
    {
        if ($order->order_type !== OrderType::TICKET
            || in_array($order->status, [OrderStatus::PENDING, OrderStatus::CANCELLED], true)) {
            return;
        }
        $memberId = $order->user?->member?->id;
        if ($memberId === null) {
            return;
        }

        foreach ($order->items()->get() as $item) {
            $product = Product::withoutGlobalScopes()->where('project_id', $order->project_id)->find($item->product_id);
            // ponytail: 回数・有効日数は決済確定時の商品設定を使う。注文後に設定変更すると差が出るため、問題になれば注文明細へ保存する。
            if ($product === null || ! $product->isTicket()) {
                continue;
            }
            MemberTicket::withoutGlobalScopes()->firstOrCreate(
                ['order_item_id' => $item->id],
                [
                    'project_id' => $order->project_id,
                    'member_id' => $memberId,
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'name' => (string) ($item->product_name ?: $product->name),
                    'total_uses' => $product->ticket_uses * (int) $item->quantity,
                    'expires_at' => $product->ticket_valid_days !== null
                        ? now()->addDays($product->ticket_valid_days)->endOfDay()
                        : null,
                ],
            );
        }
    }

    public function useOne(User $user, int $ticketId): MemberTicket
    {
        $projectId = (int) ($user->member?->project_id ?? $user->project_id ?? 0);
        if ($user->member === null || $projectId <= 0) {
            throw ValidationException::withMessages(['member' => ['会員登録が必要です']]);
        }

        return DB::transaction(function () use ($user, $projectId, $ticketId): MemberTicket {
            $member = $this->principals->lock($user, $projectId)?->member;
            if ($member === null) {
                throw ValidationException::withMessages(['member' => ['会員登録が必要です']]);
            }
            $ticket = MemberTicket::withoutGlobalScopes()
                ->where('project_id', $projectId)
                ->where('member_id', $member->id)
                ->lockForUpdate()
                ->find($ticketId);
            if ($ticket === null) {
                throw ValidationException::withMessages(['ticket' => ['回数券が見つかりません']]);
            }
            $message = match ($ticket->status()) {
                'revoked' => 'この回数券は無効になっています',
                'used_up' => 'この回数券は使い切りました',
                'expired' => 'この回数券は有効期限が切れています',
                default => null,
            };
            if ($message === null && $ticket->daily_limit !== null && MemberTicketUse::withoutGlobalScopes()
                ->where('member_ticket_id', $ticket->id)
                ->whereNull('cancelled_at')
                ->where('used_at', '>=', today())
                ->count() >= $ticket->daily_limit) {
                $message = '本日の利用上限に達しました。明日以降にご利用ください';
            }
            if ($message !== null) {
                throw ValidationException::withMessages(['ticket' => [$message]]);
            }

            $ticket->used_count++;
            $ticket->save();
            MemberTicketUse::withoutGlobalScopes()->create([
                'project_id' => $projectId,
                'member_ticket_id' => $ticket->id,
                'store_id' => $this->storeContext->store?->id,
                'used_at' => now(),
            ]);

            return $ticket;
        });
    }

    /** スタッフによる利用の取消。回数を1回分戻す。 */
    public function cancelUse(int $projectId, int $ticketId, int $useId): void
    {
        DB::transaction(function () use ($projectId, $ticketId, $useId): void {
            $ticket = MemberTicket::where('project_id', $projectId)->lockForUpdate()->findOrFail($ticketId);
            $use = MemberTicketUse::where('project_id', $projectId)
                ->where('member_ticket_id', $ticket->id)
                ->whereNull('cancelled_at')
                ->findOrFail($useId);
            $use->cancelled_at = now();
            $use->save();
            $ticket->used_count = max(0, $ticket->used_count - 1);
            $ticket->save();
        });
    }

    public function revoke(int $projectId, int $ticketId): void
    {
        MemberTicket::where('project_id', $projectId)->whereKey($ticketId)->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
