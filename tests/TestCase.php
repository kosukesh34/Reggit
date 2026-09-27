<?php

namespace Tests;

use App\Http\Middleware\ValidateCsrfToken;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\InteractsWithTestDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use InteractsWithTestDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertUsingTestDatabase();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Cache::flush();
        AdminContext::setCurrentProject(null);
    }
}
