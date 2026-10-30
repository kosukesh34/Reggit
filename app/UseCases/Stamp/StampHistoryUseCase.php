<?php

namespace App\UseCases\Stamp;

use App\Models\MemberStampTransaction;
use App\Models\User;

/** 会員のスタンプ獲得履歴（カード1枚分、新しい順） */
class StampHistoryUseCase
{
    /**
     * 説明文には管理用メモが入ることがあるため、会員には種類だけを決まった言葉で返す。会員でなければ null
     *
     * @return array<int, array{id: int, delta: int, balance_after: int, label: string, created_at: string|null}>|null
     */
    public function handle(User $user, int $stampCardId, int $limit = 30): ?array
    {
        $member = $user->member;
        if ($member === null) {
            return null;
        }

        return MemberStampTransaction::query()
            ->where('member_id', $member->id)
            ->where('stamp_card_id', $stampCardId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'delta', 'balance_after', 'order_id', 'created_at'])
            ->map(fn (MemberStampTransaction $transaction): array => [
                'id' => (int) $transaction->id,
                'delta' => (int) $transaction->delta,
                'balance_after' => (int) $transaction->balance_after,
                'label' => match (true) {
                    $transaction->delta < 0 => 'スタンプの取り消し',
                    $transaction->order_id !== null => 'ご注文',
                    default => 'スタンプ獲得',
                },
                'created_at' => $transaction->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
