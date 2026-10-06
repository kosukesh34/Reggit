<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DemoAdminUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederSecurityTest extends TestCase
{
    use RefreshDatabase;

    private array $originalEnvironment = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setEnvironment('DEMO_SEED', 'false');
        $this->setEnvironment('ADMIN_LOGIN_ID', 'seed-security-admin');
        $this->setEnvironment('ADMIN_EMAIL', '');
        $this->setEnvironment('ADMIN_PASSWORD', 'FixtureOnly#2026');
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnvironment as $name => $value) {
            Env::getRepository()->clear($name);
            if ($value !== null) {
                Env::getRepository()->set($name, $value);
            }
        }
        $this->app->instance('env', 'testing');
        parent::tearDown();
    }

    private function setEnvironment(string $name, string $value): void
    {
        if (! array_key_exists($name, $this->originalEnvironment)) {
            $this->originalEnvironment[$name] = Env::getRepository()->get($name);
        }
        Env::getRepository()->clear($name);
        Env::getRepository()->set($name, $value);
    }

    public function test_missing_admin_login_id_is_rejected(): void
    {
        $this->setEnvironment('ADMIN_LOGIN_ID', '');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_LOGIN_ID or ADMIN_EMAIL is required.');
        $this->seed(AdminUserSeeder::class);
    }

    public function test_missing_or_default_password_cannot_create_an_admin(): void
    {
        foreach (['', 'reserve'] as $password) {
            $this->setEnvironment('ADMIN_PASSWORD', $password);
            try {
                $this->seed(AdminUserSeeder::class);
                $this->fail('Unsafe administrator password was accepted.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('non-default ADMIN_PASSWORD', $exception->getMessage());
            }
            $this->assertDatabaseMissing('admin_users', ['login_id' => 'seed-security-admin']);
        }
    }

    public function test_explicit_password_creates_a_system_admin(): void
    {
        $this->seed(AdminUserSeeder::class);
        $admin = AdminUser::where('login_id', 'seed-security-admin')->firstOrFail();
        $this->assertSame(AdminUser::ROLE_SYSTEM_ADMIN, $admin->role);
        $this->assertTrue(Hash::check('FixtureOnly#2026', $admin->password));
    }

    public function test_reseeding_preserves_existing_password_and_role(): void
    {
        $admin = AdminUser::factory()->create([
            'login_id' => 'seed-security-admin',
            'password' => 'ExistingFixture#2026',
            'role' => AdminUser::ROLE_STAFF,
        ]);
        $passwordHash = $admin->password;
        $this->setEnvironment('ADMIN_PASSWORD', '');
        $this->seed(AdminUserSeeder::class);
        $admin->refresh();
        $this->assertSame($passwordHash, $admin->password);
        $this->assertSame(AdminUser::ROLE_STAFF, $admin->role);
    }

    public function test_demo_reseeding_preserves_existing_password(): void
    {
        $this->setEnvironment('DEMO_SEED', 'true');
        $this->setEnvironment('DEMO_SYSTEM_LOGIN_ID', 'seed-security-admin');
        $admin = AdminUser::factory()->create([
            'login_id' => 'seed-security-admin',
            'password' => 'ExistingFixture#2026',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
        ]);
        $passwordHash = $admin->password;
        $this->seed(AdminUserSeeder::class);
        $this->seed(DemoAdminUsersSeeder::class);
        $this->assertSame($passwordHash, $admin->fresh()->password);
    }

    public function test_production_rejects_direct_demo_admin_helper(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(\RuntimeException::class);
        AdminUserSeeder::upsertDocumentedDemoSystemAdmin();
    }

    public function test_production_rejects_demo_seed_before_creating_accounts(): void
    {
        $this->setEnvironment('DEMO_SEED', 'true');
        $this->app->instance('env', 'production');
        foreach ([new AdminUserSeeder, new DemoAdminUsersSeeder] as $seeder) {
            try {
                $seeder->run();
                $this->fail('Production demo seed was accepted.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('production', $exception->getMessage());
            }
        }
        $this->assertDatabaseCount('admin_users', 0);
    }
}
