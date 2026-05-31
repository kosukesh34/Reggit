<?php

namespace Tests\Unit;

use App\Models\AdminUser;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\Concerns\UpsertsAdminUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpsertsAdminUsersTest extends TestCase
{
    use RefreshDatabase;
    use UpsertsAdminUsers;

    public function test_パスワード不一致の既存管理者は再seedで修復されること(): void
    {
        AdminUser::factory()->create([
            'login_id' => 'demo_sys',
            'password' => 'wrong-password',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
        ]);

        AdminUserSeeder::upsertDocumentedDemoSystemAdmin();

        $user = AdminUser::where('login_id', 'demo_sys')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check(AdminUserSeeder::demoSystemPassword(), $user->password));
    }

    public function test_env切り捨て相当の既存パスワードは再seedで修復されること(): void
    {
        AdminUser::factory()->create([
            'login_id' => 'demo_sys',
            'password' => 'DemoSys',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
        ]);

        AdminUserSeeder::upsertDocumentedDemoSystemAdmin();

        $user = AdminUser::where('login_id', 'demo_sys')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('DemoSys#2026', $user->password));
    }

    public function test_正しいパスワードの既存管理者は再seedで上書きされないこと(): void
    {
        $expected = AdminUserSeeder::demoSystemPassword();
        AdminUser::factory()->create([
            'login_id' => 'demo_sys',
            'password' => $expected,
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
        ]);

        AdminUserSeeder::upsertDocumentedDemoSystemAdmin();

        $user = AdminUser::where('login_id', 'demo_sys')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check($expected, $user->password));
    }

    public function test_resolve_demo_passwordはenv切り捨てを補正すること(): void
    {
        putenv('DEMO_SYSTEM_PASSWORD=DemoSys');
        $_ENV['DEMO_SYSTEM_PASSWORD'] = 'DemoSys';
        $_SERVER['DEMO_SYSTEM_PASSWORD'] = 'DemoSys';

        $this->assertSame('DemoSys#2026', self::resolveDemoPassword('DEMO_SYSTEM_PASSWORD', 'DemoSys#2026', ['DemoSys']));
    }

    public function test_開発用管理者が空なら復旧されること(): void
    {
        putenv('DEMO_SEED=false');
        $_ENV['DEMO_SEED'] = 'false';
        $_SERVER['DEMO_SEED'] = 'false';

        $this->assertSame(0, AdminUser::count());

        app(AdminUserSeeder::class)->ensureDevelopmentAdminsExist();

        $user = AdminUser::where('login_id', 'demo_sys')->first();
        $this->assertNotNull($user);
        $this->assertSame(AdminUser::ROLE_SYSTEM_ADMIN, $user->role);
        $this->assertTrue(Hash::check(AdminUserSeeder::demoSystemPassword(), $user->password));
    }
}
