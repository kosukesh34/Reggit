<?php

namespace Tests\Unit;

use App\Support\AdminLoginIpAllowlist;
use PHPUnit\Framework\TestCase;

class AdminLoginIpAllowlistTest extends TestCase
{
    public function test_allows_空の許可リストは任意のipを許可すること(): void
    {
        $this->assertTrue(AdminLoginIpAllowlist::allows('203.0.113.10', []));
        $this->assertSame([], AdminLoginIpAllowlist::parse(null));
        $this->assertSame([], AdminLoginIpAllowlist::parse('  '));
    }

    public function test_allows_完全一致とcidrで判定すること(): void
    {
        $entries = AdminLoginIpAllowlist::parse("203.0.113.10\n198.51.100.0/24");
        $this->assertTrue(AdminLoginIpAllowlist::allows('203.0.113.10', $entries));
        $this->assertTrue(AdminLoginIpAllowlist::allows('198.51.100.55', $entries));
        $this->assertFalse(AdminLoginIpAllowlist::allows('203.0.113.11', $entries));
        $this->assertFalse(AdminLoginIpAllowlist::allows(null, $entries));
    }

    public function test_is_valid_entry_エントリの妥当性を検証すること(): void
    {
        $this->assertTrue(AdminLoginIpAllowlist::isValidEntry('203.0.113.10'));
        $this->assertTrue(AdminLoginIpAllowlist::isValidEntry('203.0.113.0/24'));
        $this->assertFalse(AdminLoginIpAllowlist::isValidEntry('not-an-ip'));
        $this->assertFalse(AdminLoginIpAllowlist::isValidEntry('203.0.113.0/99'));
    }
}
