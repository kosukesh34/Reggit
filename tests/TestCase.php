<?php

namespace Tests;

use App\Http\Middleware\ValidateCsrfToken;
use App\Util\AdminContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\InteractsWithTestDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use InteractsWithTestDatabase;

    public function actingAs(Authenticatable $user, $guard = null)
    {
        parent::actingAs($user, $guard);
        if ($guard === 'admin') {
            $this->withSession(['password_hash_admin' => auth('admin')->hashPasswordForCookie($user->getAuthPassword())]);
        }

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertUsingTestDatabase();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Cache::flush();
        AdminContext::setCurrentProject(null);
    }
}
