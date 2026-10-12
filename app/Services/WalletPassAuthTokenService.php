<?php

namespace App\Services;

use App\Models\Member;

class WalletPassAuthTokenService
{
    public function createForMember(Member $member): string
    {
        $secret = (string) config('wallet_pass.auth_token_secret', '');
        if ($secret === '') {
            $secret = (string) config('app.key', '');
        }
        if ($secret === '') {
            throw new \RuntimeException('Wallet pass authentication secret is not configured.');
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
        if ((string) config('wallet_pass.auth_token_secret', '') === ''
            && (string) config('app.key', '') === '') {
            return false;
        }
        $expected = $this->createForMember($member);

        return hash_equals($expected, trim($token));
    }
}
