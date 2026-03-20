<?php

namespace Tests;

use App\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        // MySQL コンテナが稀に再起動/切断されることがあり、テスト開始〜アプリ起動時に DB が不通だと
        // "Connection refused / server has gone away / getaddrinfo for db failed" になって連鎖的に落ちます。
        //
        // RefreshDatabase 等の都合で、DB への接続失敗が `parent::setUp()` 中にも発生し得るため、
        // `parent::setUp()` 自体を含めてリトライして耐性を上げます。
        $retryableSubstrings = [
            'Connection refused',
            'server has gone away',
            'MySQL server has gone away',
            'SQLSTATE[HY000] [2002]',
            'php_network_getaddresses: getaddrinfo for db failed',
            'Name or service not known',
        ];

        $maxRetries = 20;
        $sleepMicros = 250_000; // 0.25s
        $lastError = null;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                parent::setUp();
                $lastError = null;
                break;
            } catch (Throwable $e) {
                $lastError = $e;

                $message = $e->getMessage();
                $isRetryable = false;
                foreach ($retryableSubstrings as $needle) {
                    if (str_contains($message, $needle)) {
                        $isRetryable = true;
                        break;
                    }
                }

                if (! $isRetryable) {
                    throw $e;
                }

                // 次の試行までに DB 接続状態をクリアしておく（失敗しても無視）
                try {
                    DB::purge();
                } catch (Throwable) {
                    // ignore
                }

                usleep($sleepMicros);
            }
        }

        if ($lastError !== null) {
            throw $lastError;
        }

        $this->withoutMiddleware(ValidateCsrfToken::class);

        // アプリ起動後も念のため、確実に PDO へ接続できるまで短く待つ
        $lastError = null;
        for ($attempt = 0; $attempt < 10; $attempt++) {
            try {
                DB::connection()->getPdo();
                $lastError = null;
                break;
            } catch (Throwable $e) {
                $lastError = $e;
                usleep(250_000);
            }
        }

        if ($lastError !== null) {
            throw $lastError;
        }
    }
}
