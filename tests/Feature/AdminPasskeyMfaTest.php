<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Support\AdminPasskeyGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passkeys\Passkey;
use Tests\TestCase;

class AdminPasskeyMfaTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_パスキー未登録時は登録画面へ進むこと(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        AdminUser::factory()->create([
            'login_id' => 'store_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $this->post(route('admin.login.submit'), [
            'login_id' => 'store_admin',
            'password' => 'password',
        ])->assertRedirect(route('admin.passkey.enroll'));

        $this->assertAuthenticated('admin');
        $this->get(route('admin.dashboard', ['project_key' => $project->project_key]))
            ->assertRedirect(route('admin.passkey.enroll'));
        $this->get(route('admin.passkey.enroll'))
            ->assertOk()
            ->assertSee('パスキー登録');
    }

    public function test_login_パスキー登録済み時は確認画面へ進むこと(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $admin = AdminUser::factory()->create([
            'login_id' => 'store_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $this->createPasskey($admin, 'MacBook');

        $this->post(route('admin.login.submit'), [
            'login_id' => 'store_admin',
            'password' => 'password',
        ])->assertRedirect(route('admin.passkey.verify'));

        $this->get(route('admin.dashboard', ['project_key' => $project->project_key]))
            ->assertRedirect(route('admin.passkey.verify'));
        $this->get(route('admin.passkey.verify'))
            ->assertOk()
            ->assertSee('パスキーで続行');
    }

    public function test_admin_パスキー確認済みセッションで管理画面を開けること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $this->createPasskey($admin, 'MacBook');

        $this->actingAs($admin, 'admin')
            ->withSession([
                AdminPasskeyGate::PASSWORD_AUTHENTICATED => true,
                AdminPasskeyGate::PASSKEY_VERIFIED => true,
            ])
            ->get(route('admin.dashboard', ['project_key' => $project->project_key]))
            ->assertOk();
    }

    public function test_settings_複数パスキーの一覧表示と削除ができること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $first = $this->createPasskey($admin, 'MacBook');
        $second = $this->createPasskey($admin, 'iPhone');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.passkey.settings'))
            ->assertOk()
            ->assertSee('ログイン設定')
            ->assertSee('パスワード変更')
            ->assertSee('MacBook')
            ->assertSee('iPhone');

        $this->actingAs($admin, 'admin')
            ->delete(route('passkey.destroy', $first))
            ->assertRedirect();

        $this->assertDatabaseMissing('passkeys', ['id' => $first->id]);
        $this->assertDatabaseHas('passkeys', ['id' => $second->id]);
    }

    public function test_login_システム管理者はパスキーをバイパスすること(): void
    {
        AdminUser::factory()->create([
            'login_id' => 'sys_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => null,
        ]);

        $this->post(route('system-admin.login.submit'), [
            'login_id' => 'sys_admin',
            'password' => 'password',
        ])->assertRedirect(route('system-admin.projects.index'));

        $this->get(route('system-admin.projects.index'))
            ->assertOk();
        $this->assertTrue((bool) session(AdminPasskeyGate::PASSKEY_VERIFIED));
    }

    public function test_verify_パスキー確認済みでintended先が開けること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $admin = AdminUser::factory()->create([
            'login_id' => 'store_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $this->createPasskey($admin, 'MacBook');

        $this->post(route('admin.login.submit'), [
            'login_id' => 'store_admin',
            'password' => 'password',
        ])->assertRedirect(route('admin.passkey.verify'));

        $this->withSession([AdminPasskeyGate::PASSKEY_VERIFIED => true])
            ->get(route('admin.dashboard', ['project_key' => $project->project_key]))
            ->assertOk();
    }

    private function createPasskey(AdminUser $admin, string $name): Passkey
    {
        return $admin->passkeys()->create([
            'name' => $name,
            'credential_id' => 'cred-'.uniqid($name, true),
            'credential' => [
                'publicKeyCredentialId' => 'cred',
                'type' => 'public-key',
                'transports' => [],
                'attestationType' => 'none',
                'trustPath' => ['type' => 'empty'],
                'aaguid' => '00000000-0000-0000-0000-000000000000',
                'credentialPublicKey' => 'test',
                'userHandle' => null,
                'counter' => 0,
            ],
        ]);
    }
}
