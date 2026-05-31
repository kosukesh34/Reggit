<?php

namespace Tests\Concerns;

/**
 * テスト実行時に開発 DB (reggit) を RefreshDatabase で消さないためのガード。
 *
 * Docker Compose の env_file (.env) が DB_DATABASE=reggit をプロセス環境に注入するため、
 * .env.testing の reggit_test より優先されてしまうことがある。
 */
trait InteractsWithTestDatabase
{
    protected function forceTestDatabaseEnvironment(): void
    {
        $database = 'reggit_test';

        putenv('DB_DATABASE='.$database);
        $_ENV['DB_DATABASE'] = $database;
        $_SERVER['DB_DATABASE'] = $database;
    }

    protected function assertUsingTestDatabase(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        $expected = 'reggit_test';
        $database = (string) config('database.connections.'.config('database.default').'.database');

        if ($database !== $expected) {
            \PHPUnit\Framework\Assert::fail(
                "テストは開発 DB (reggit) ではなく {$expected} に接続する必要があります。"
                ." 現在: {$database}。"
                .' make test-env && make test-db && make test を実行してください。'
            );
        }
    }
}
