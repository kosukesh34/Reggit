<?php

namespace App\Domain\Member\Services;

use App\Enums\PointTransactionType;
use DateTimeImmutable;
use DateTimeInterface;

/** Replays the ledger, spending the points with the earliest expiry first. */
class PointLedgerBalanceCalculator
{
    /** @return array{balance: int, expires_at: ?DateTimeImmutable, points_expiring: int, has_transactions: bool} */
    public function calculate(array $transactions, DateTimeInterface $now): array
    {
        $lots = [];
        $spentByOrder = [];
        $debt = 0;
        foreach ($transactions as $transaction) {
            $at = $this->date($transaction['created_at'] ?? $now);
            $points = (int) $transaction['points'];
            $type = $transaction['type'];
            $orderId = $transaction['order_id'] ?? null;
            if ($points > 0) {
                $restored = $type === PointTransactionType::REFUNDED && $orderId !== null
                    ? ($spentByOrder[$orderId] ?? []) : [];
                $unrestored = [];
                foreach ($restored as $lot) {
                    $amount = min($points, $lot['points']);
                    if ($lot['points'] > $amount) {
                        $unrestored[] = array_replace($lot, ['points' => $lot['points'] - $amount]);
                    }
                    $lot['points'] = $amount;
                    $lots[] = $lot;
                    $points -= $amount;
                }
                if ($restored !== []) {
                    $spentByOrder[$orderId] = $unrestored;
                }
                $settled = min($points, $debt);
                $points -= $settled;
                $debt -= $settled;
                if ($points > 0) {
                    $expiry = isset($transaction['expires_at']) ? $this->date($transaction['expires_at'])->setTime(23, 59, 59) : null;
                    $lots[] = ['points' => $points, 'expires_at' => $expiry, 'order_id' => $orderId];
                }

                continue;
            }

            usort($lots, static fn (array $a, array $b): int => ($a['expires_at'] ?? new DateTimeImmutable('9999-12-31')) <=> ($b['expires_at'] ?? new DateTimeImmutable('9999-12-31')));
            $remaining = abs($points);
            foreach ($lots as &$lot) {
                $expired = $lot['expires_at'] !== null && $lot['expires_at'] < $at;
                if ($type !== PointTransactionType::EXPIRED && $expired) {
                    continue;
                }
                $amount = min($remaining, $lot['points']);
                if ($type === PointTransactionType::USED && $orderId !== null && $amount > 0) {
                    $spentByOrder[$orderId][] = array_replace($lot, ['points' => $amount]);
                }
                $lot['points'] -= $amount;
                $remaining -= $amount;
                if ($remaining === 0) {
                    break;
                }
            }
            unset($lot);
            // Reversals and expiry never create a negative balance.
            if (! in_array($type, [PointTransactionType::REVERSED, PointTransactionType::EXPIRED], true)) {
                $debt += $remaining;
            }
        }

        $balance = -$debt;
        $earliest = null;
        $expiring = 0;
        foreach ($lots as $lot) {
            if ($lot['points'] <= 0 || ($lot['expires_at'] !== null && $lot['expires_at'] < $now)) {
                continue;
            }
            $balance += $lot['points'];
            if ($lot['expires_at'] === null) {
                continue;
            }
            if ($earliest === null || $lot['expires_at'] < $earliest) {
                $earliest = $lot['expires_at'];
                $expiring = 0;
            }
            if ($lot['expires_at'] == $earliest) {
                $expiring += $lot['points'];
            }
        }

        return ['balance' => $balance, 'expires_at' => $earliest, 'points_expiring' => $expiring, 'has_transactions' => $transactions !== []];
    }

    private function date(DateTimeInterface|string $value): DateTimeImmutable
    {
        return $value instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($value) : new DateTimeImmutable($value);
    }
}
