<?php

namespace Tests\Unit;

use App\Models\Member;
use App\Services\WalletPassAuthTokenService;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WalletPassAuthTokenServiceTest extends TestCase
{
    private function member(): Member
    {
        return (new Member)->forceFill(['id' => 42, 'project_id' => 7, 'member_number' => 'member-42']);
    }

    #[TestWith([null])]
    #[TestWith([''])]
    public function test_missing_keys_reject_predictable_tokens_and_refuse_issuance(?string $appKey): void
    {
        config(['wallet_pass.auth_token_secret' => '', 'app.key' => $appKey]);
        $service = app(WalletPassAuthTokenService::class);
        $member = $this->member();
        foreach (['', 'wallet-pass-default-secret'] as $knownKey) {
            $forged = hash_hmac('sha256', 'wallet-pass:7:42:member-42', $knownKey);
            $this->assertFalse($service->verify($member, $forged));
        }
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Wallet pass authentication secret is not configured.');
        $service->createForMember($member);
    }

    #[TestWith(['custom-wallet-key', 'app-key', 'custom-wallet-key'])]
    #[TestWith(['', 'app-key', 'app-key'])]
    public function test_configured_keys_keep_existing_tokens_valid(string $walletKey, string $appKey, string $expectedKey): void
    {
        config(['wallet_pass.auth_token_secret' => $walletKey, 'app.key' => $appKey]);
        $service = app(WalletPassAuthTokenService::class);
        $member = $this->member();
        $existing = hash_hmac('sha256', 'wallet-pass:7:42:member-42', $expectedKey);
        $this->assertSame($existing, $service->createForMember($member));
        $this->assertTrue($service->verify($member, $existing));
        $other = (new Member)->forceFill(['id' => 43, 'project_id' => 8, 'member_number' => 'member-43']);
        $this->assertFalse($service->verify($other, $existing));
    }
}
