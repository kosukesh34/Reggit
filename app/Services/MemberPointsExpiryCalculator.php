<?php

namespace App\Services;

use App\Models\Member;
use App\Models\PointTransaction;

class MemberPointsExpiryCalculator
{
    /** @return array{expires_at: string, expires_at_label: string, points_expiring: int}|null */
    public function getExpiryInfo(Member $member): ?array
    {
        $earliest = PointTransaction::where('member_id', $member->id)
            ->where('type', 'earned')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->first();

        if ($earliest === null) {
            return null;
        }

        $pointsExpiring = PointTransaction::where('member_id', $member->id)
            ->where('type', 'earned')
            ->where('expires_at', $earliest->expires_at)
            ->sum('points');

        return [
            'expires_at' => $earliest->expires_at->format('Y-m-d'),
            'expires_at_label' => $earliest->expires_at->format('Y年n月j日'),
            'points_expiring' => (int) $pointsExpiring,
        ];
    }
}
