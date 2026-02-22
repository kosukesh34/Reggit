<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Support\Str;

class MemberService
{
    public function __construct(
        private CouponIssueService $couponIssueService
    ) {
    }

    public function getMemberData(User $user): array
    {
        $member = $user->member;

        if ($member === null) {
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

        $rankInfo = $this->getRankInfo($member);
        $expiryInfo = $this->getPointsExpiryInfo($member);

        return [
            'is_member' => true,
            'member' => $member->load('user'),
            'user' => $member->user,
            'points' => $member->getTotalPointsFromTransactions(),
            'current_rank' => $rankInfo['current_rank'],
            'current_rank_label' => $rankInfo['current_rank_label'],
            'current_rank_icon' => $rankInfo['current_rank_icon'],
            'current_rank_background' => $rankInfo['current_rank_background'],
            'next_rank' => $rankInfo['next_rank'],
            'next_rank_label' => $rankInfo['next_rank_label'],
            'points_to_next_rank' => $rankInfo['points_to_next_rank'],
            'rank_basis' => $rankInfo['rank_basis'],
            'rank_metric' => $rankInfo['rank_metric'],
            'points_expiry' => $expiryInfo,
        ];
    }

    public function updateProfile(User $user, array $data): array
    {
        $member = $user->member;
        if ($member === null) {
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

        $projectId = $user->project_id ?? \App\Util\AdminContext::getCurrentProjectId() ?? 1;
        $rankSettings = \App\Models\ShopSetting::getMemberRankSettings($projectId);
        $defaultRankKey = collect($rankSettings)->first(fn ($r) => ($r['is_active'] ?? false) === true)['key'] ?? 'bronze';
        $member = Member::create([
            'project_id' => $projectId,
            'user_id' => $user->id,
            'member_number' => $memberNumber,
            'points' => 0,
            'status' => MemberStatus::ACTIVE,
            'rank' => $defaultRankKey,
            'birthday' => $data['birthday'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        $this->couponIssueService->issueForFirstRegistration($member);

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
        $rankInfo = $this->getRankInfo($member);
        if (($rankInfo['current_rank'] ?? '') !== '' && $rankInfo['current_rank'] !== $member->rank) {
            $member->update(['rank' => $rankInfo['current_rank']]);
        }
    }

    private function getRankInfo(Member $member): array
    {
        $projectId = $member->project_id ?? \App\Util\AdminContext::getCurrentProjectId() ?? 1;
        $ranks = \App\Models\ShopSetting::getMemberRankSettings($projectId);
        $basis = \App\Models\ShopSetting::getRankBasis($projectId);

        $points = $member->points ?? 0;
        $stamps = $member->getTotalStamps();
        $metric = $basis === 'stamps' ? $stamps : ($basis === 'max' ? max($points, $stamps) : $points);

        $active = array_values(array_filter($ranks, fn ($r) => ($r['is_active'] ?? false) === true));
        if ($active === []) {
            return [
                'current_rank' => $member->rank ?? 'bronze',
                'current_rank_label' => $member->rank ?? 'bronze',
                'current_rank_icon' => null,
                'current_rank_background' => null,
                'next_rank' => null,
                'next_rank_label' => null,
                'points_to_next_rank' => null,
                'rank_basis' => $basis,
                'rank_metric' => $metric,
            ];
        }

        usort($active, fn ($a, $b) => ($a['min_points'] <=> $b['min_points']) ?: ($a['order'] <=> $b['order']));
        $resolved = $active[0];
        foreach ($active as $rank) {
            if ($metric >= ($rank['min_points'] ?? 0)) {
                $resolved = $rank;
            }
        }

        $next = null;
        foreach ($active as $rank) {
            if (($rank['min_points'] ?? 0) > ($resolved['min_points'] ?? 0)) {
                $next = $rank;
                break;
            }
        }

        $pointsToNext = null;
        if ($next !== null) {
            $pointsToNext = max(0, ($next['min_points'] ?? 0) - $metric);
        }

        return [
            'current_rank' => $resolved['key'] ?? 'bronze',
            'current_rank_label' => $resolved['label'] ?? ($resolved['key'] ?? 'bronze'),
            'current_rank_icon' => $resolved['icon_url'] ?? null,
            'current_rank_background' => $resolved['stamp_background_url'] ?? null,
            'next_rank' => $next !== null ? ($next['key'] ?? null) : null,
            'next_rank_label' => $next !== null ? ($next['label'] ?? ($next['key'] ?? null)) : null,
            'points_to_next_rank' => $pointsToNext,
            'rank_basis' => $basis,
            'rank_metric' => $metric,
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
