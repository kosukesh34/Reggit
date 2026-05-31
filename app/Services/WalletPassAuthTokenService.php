<?php

namespace App\Services;

use App\Models\Member;

class WalletPassAuthTokenService
{
    public function createForMember(Member $member): string
    {
        $secret = (string) config('wallet_pass.auth_token_secret', '');
        if ($secret === '') {
            // secret 未設定でも pass 自体は発行できるよう固定fallbackで生成
            $secret = (string) config('app.key', 'wallet-pass-default-secret');
        }

        $payload = sprintf(
            'wallet-pass:%d:%d:%s',
            (int) $member->project_id,
            (int) $member->id,
            (string) ($member->member_number ?? '')
        );

        return hash_hmac('sha256', $payload, $secret);
    }

    public function verify(Member $member, string $token): bool
    {
        $expected = $this->createForMember($member);

        return hash_equals($expected, trim($token));
    }
}
