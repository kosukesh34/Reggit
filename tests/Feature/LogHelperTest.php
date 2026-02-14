<?php

namespace Tests\Feature;

use App\Util\LogHelper;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogHelperTest extends TestCase
{
    public function testAddsProjectIdToContext(): void
    {
        Log::spy();

        LogHelper::info('test-message', []);

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
            return $message === 'test-message' && array_key_exists('project_id', $context);
        })->once();
    }
}
