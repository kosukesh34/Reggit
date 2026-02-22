<?php

namespace App\UseCases\Member\Data;

/**
 * ポイントカード画面用の表示データ。不変。
 * @param array{member_number: string, points: int, rank: string|null, rank_label: string|null, rank_icon: string|null, rank_background: string|null, points_expiry: array{expires_at: string, expires_at_label: string, points_expiring: int}|null} $data
 */
class MemberPointCardView
{
    public function __construct(
        public bool $requiresMembership,
        public array $data
    ) {
    }
}
