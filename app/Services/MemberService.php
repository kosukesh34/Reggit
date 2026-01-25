<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Str;

class MemberService
{
    public function getMemberData(User $user): array
    {
        $member = $user->member;

        if (!$member) {
            return [
                'is_member' => false,
                'message' => '会員登録がまだです',
            ];
        }

        return [
            'is_member' => true,
            'member' => $member->load('user'),
            'points' => $member->points,
        ];
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
            'birthday' => $data['birthday'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        if (isset($data['phone'])) {
            $user->update(['phone' => $data['phone']]);
        }

        return $member->load('user');
    }

    private function generateMemberNumber(): string
    {
        return 'MEM-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }
}

