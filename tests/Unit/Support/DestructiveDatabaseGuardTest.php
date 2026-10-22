<?php

namespace Tests\Unit\Support;

use App\Support\DestructiveDatabaseGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DestructiveDatabaseGuardTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_should_prohibit(string $database, string $env, bool $allow, bool $expected): void
    {
        $this->assertSame(
            $expected,
            DestructiveDatabaseGuard::shouldProhibit($database, $env, $allow),
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool, 3: bool}>
     */
    public static function cases(): array
    {
        return [
            'production always' => ['anything', 'production', false, true],
            'production allow override' => ['anything', 'production', true, false],
            'dev reggit blocked' => ['reggit', 'local', false, true],
            'dev reggit_test allowed' => ['reggit_test', 'local', false, false],
            'testing reggit_test allowed' => ['reggit_test', 'testing', false, false],
            'allow flag on reggit' => ['reggit', 'local', true, false],
        ];
    }
}
