<?php

namespace App\Services;

use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Support\Str;

class MemberService
{
    /** ランク閾値: キー=ランク名, 値=必要な累計ポイント（以上） */
    private const RANK_TIERS = [
        'bronze' => 0,
        'silver' => 1000,
        'gold' => 5000,
        'platinum' => 10000,
    ];

    private const RANK_LABELS = [
        'bronze' => 'ブロンズ',
        'silver' => 'シルバー',
        'gold' => 'ゴールド',
        'platinum' => 'プラチナ',
    ];

    /** ポイント有効期限（獲得から何日後） */
    private const POINTS_VALID_DAYS = 365;

    public function getMemberData(User $user): array
    {
        $member = $user->member;

        if (!$member) {
            return [
                'is_member' => false,
                'message' => '会員登録がまだです',
            ];
        }

        $totalPoints = $member->getTotalPointsFromTransactions();
        if ($member->points !== $totalPoints) {
            $member->update(['points' => $totalPoints]);
        }
        $this->syncMemberRank($member);
        $member->refresh();

        $rankInfo = $this->getRankInfo($totalPoints, $member->rank);
        $expiryInfo = $this->getPointsExpiryInfo($member);

        return [
            'is_member' => true,
            'member' => $member->load('user'),
            'user' => $member->user,
            'points' => $member->getTotalPointsFromTransactions(),
            'current_rank' => $rankInfo['current_rank'],
            'current_rank_label' => $rankInfo['current_rank_label'],
            'next_rank' => $rankInfo['next_rank'],
            'next_rank_label' => $rankInfo['next_rank_label'],
            'points_to_next_rank' => $rankInfo['points_to_next_rank'],
            'points_expiry' => $expiryInfo,
        ];
    }

    public function updateProfile(User $user, array $data): array
    {
        $member = $user->member;
        if (!$member) {
            throw new \Exception('会員登録が必要です');
        }

        if (isset($data['name'])) {
            $user->update(['name' => $data['name']]);
        }
        if (array_key_exists('phone', $data)) {
            $user->update(['phone' => $data['phone']]);
        }
        if (array_key_exists('birthday', $data)) {
            $member->update(['birthday' => $data['birthday'] ?: null]);
        }
        if (array_key_exists('address', $data)) {
            $member->update(['address' => $data['address'] ?: null]);
        }

        return $this->getMemberData($user);
    }

    public function registerMember(User $user, array $data): Member
    {
        if ($user->member) {
            throw new \Exception('既に会員登録済みです');
        }

        $memberNumber = $this->generateMemberNumber();

        $member = Member::create([
            'user_id' => $user->id,
            'member_number' => $memberNumber,
            'points' => 0,
            'status' => 'active',
            'rank' => 'bronze',
            'birthday' => $data['birthday'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        if (isset($data['phone'])) {
            $user->update(['phone' => $data['phone']]);
        }
        if (isset($data['name'])) {
            $user->update(['name' => $data['name']]);
        }

        return $member->load('user');
    }

    private function syncMemberRank(Member $member): void
    {
        $rankInfo = $this->getRankInfo($member->points, $member->rank);
        if ($rankInfo['current_rank'] !== $member->rank) {
            $member->update(['rank' => $rankInfo['current_rank']]);
        }
    }

    private function getRankInfo(int $points, string $currentRank): array
    {
        $tiers = self::RANK_TIERS;
        $labels = self::RANK_LABELS;
        $rankKeys = array_keys($tiers);
        $resolvedRank = 'bronze';

        foreach ($rankKeys as $key) {
            if ($points >= $tiers[$key]) {
                $resolvedRank = $key;
            }
        }

        $nextRank = null;
        $pointsToNext = null;
        $nextIndex = array_search($resolvedRank, $rankKeys);
        if ($nextIndex !== false && $nextIndex < count($rankKeys) - 1) {
            $nextRank = $rankKeys[$nextIndex + 1];
            $nextThreshold = $tiers[$nextRank];
            $pointsToNext = max(0, $nextThreshold - $points);
        }

        return [
            'current_rank' => $resolvedRank,
            'current_rank_label' => $labels[$resolvedRank] ?? $resolvedRank,
            'next_rank' => $nextRank,
            'next_rank_label' => $nextRank ? ($labels[$nextRank] ?? $nextRank) : null,
            'points_to_next_rank' => $pointsToNext,
        ];
    }

    private function getPointsExpiryInfo(Member $member): ?array
    {
        $earliest = PointTransaction::where('member_id', $member->id)
            ->where('type', 'earned')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->first();

        if (!$earliest) {
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

    private function generateMemberNumber(): string
    {
        $digits = '0123456789';
        $length = 10;
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $candidate = '';
            for ($j = 0; $j < $length; $j++) {
                $candidate .= $digits[random_int(0, 9)];
            }
            if (!Member::where('member_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return sprintf('%010d', (time() + random_int(0, 999999)) % 10000000000);
    }
}
