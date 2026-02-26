<?php

namespace Tests\Feature;

use App\Util\LogHelper;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogHelperTest extends TestCase
{
    public function test_log_プロジェクトIDがコンテキストに含まれること(): void
    {
        Log::spy();

        LogHelper::info('test-message', []);

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
            return $message === 'test-message' && array_key_exists('project_id', $context);
        })->once();
    }
}
