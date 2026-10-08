<?php

namespace App\Services;

use App\Models\Member;

class MemberPointsExpiryCalculator
{
    /** @return array{expires_at: string, expires_at_label: string, points_expiring: int}|null */
    public function getExpiryInfo(Member $member): ?array
    {
        $balance = $member->getPointBalance();
        $earliest = $balance['expires_at'];
        if ($earliest === null) {
            return null;
        }

        return [
            'expires_at' => $earliest->format('Y-m-d'),
            'expires_at_label' => $earliest->format('Y年n月j日'),
            'points_expiring' => $balance['points_expiring'],
        ];
    }
}
