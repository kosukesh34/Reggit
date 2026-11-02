<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Support\AdminPasskeyGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Passkeys\Passkey;
use Tests\TestCase;

class AdminPasskeyRemoteEnrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_remote_enroll_設定画面から遠隔登録リンクを発行できること(): void
    {
        [$admin] = $this->adminWithPasskey();

        $this->actingAs($admin, 'admin')
            ->withSession([
                AdminPasskeyGate::PASSWORD_AUTHENTICATED => true,
                AdminPasskeyGate::PASSKEY_VERIFIED => true,
            ])
            ->post(route('admin.passkey.remote-enroll.create'))
            ->assertRedirect()
            ->assertSessionHas('remote_enroll_url');
    }

    public function test_remote_enroll_ゲストがリンク後パスワードログインで既存パスキーを消さず登録へ進むこと(): void
    {
        [$admin, $passkey] = $this->adminWithPasskey([
            'login_id' => 'store_admin',
            'password' => 'password',
        ]);
        $url = URL::temporarySignedRoute(
            'admin.passkey.remote-enroll.confirm',
            now()->addMinutes(30),
            ['admin' => $admin->id, 'hash' => sha1((string) $admin->email)],
        );

        $this->get($url)
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('status');

        $this->post(route('admin.login.submit'), [
            'login_id' => 'store_admin',
            'password' => 'password',
        ])->assertRedirect(route('admin.passkey.enroll'));

        $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
        $this->get(route('admin.passkey.enroll'))
            ->assertOk()
            ->assertSee('パスキー登録');
    }

    public function test_remote_enroll_ローカルでパスキーを省いていても遠隔登録のリンクからは登録へ進むこと(): void
    {
        [$admin] = $this->adminWithPasskey(['login_id' => 'store_admin', 'password' => 'password']);
        $this->app['env'] = 'local';
        config(['passkeys.admin_required' => false]);
        $url = URL::temporarySignedRoute(
            'admin.passkey.remote-enroll.confirm',
            now()->addMinutes(30),
            ['admin' => $admin->id, 'hash' => sha1((string) $admin->email)],
        );

        $this->get($url)->assertRedirect(route('admin.login'));
        $this->post(route('admin.login.submit'), ['login_id' => 'store_admin', 'password' => 'password'])
            ->assertRedirect(route('admin.passkey.enroll'));
        $this->get(route('admin.passkey.enroll'))->assertOk()->assertSee('パスキー登録');
    }

    public function test_remote_enroll_確認済みセッションで自分のリンクから登録へ進むこと(): void
    {
        [$admin] = $this->adminWithPasskey();
        $url = URL::temporarySignedRoute(
            'admin.passkey.remote-enroll.confirm',
            now()->addMinutes(30),
            ['admin' => $admin->id, 'hash' => sha1((string) $admin->email)],
        );

        $this->actingAs($admin, 'admin')
            ->withSession([
                AdminPasskeyGate::PASSWORD_AUTHENTICATED => true,
                AdminPasskeyGate::PASSKEY_VERIFIED => true,
            ])
            ->get($url)
            ->assertRedirect(route('admin.passkey.enroll'));

        $this->get(route('admin.passkey.enroll'))
            ->assertOk()
            ->assertSee('パスキー登録');
    }

    public function test_remote_enroll_別管理者のリンクでは解除されないこと(): void
    {
        [$admin] = $this->adminWithPasskey();
        $other = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $admin->project_id,
            'password' => 'password',
            'login_id' => 'other_admin',
            'email' => 'other@example.test',
        ]);
        $other->passkeys()->create([
            'name' => 'Other Phone',
            'credential_id' => 'cred-'.uniqid('other', true),
            'credential' => [
                'publicKeyCredentialId' => 'cred-other',
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
        $url = URL::temporarySignedRoute(
            'admin.passkey.remote-enroll.confirm',
            now()->addMinutes(30),
            ['admin' => $admin->id, 'hash' => sha1((string) $admin->email)],
        );

        $this->get($url)->assertRedirect(route('admin.login'));

        $this->post(route('admin.login.submit'), [
            'login_id' => 'other_admin',
            'password' => 'password',
        ])->assertRedirect(route('admin.passkey.verify'));

        $this->assertFalse((bool) session(AdminPasskeyGate::REMOTE_ENROLL));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: AdminUser, 1: Passkey}
     */
    private function adminWithPasskey(array $overrides = []): array
    {
        $project = Project::factory()->create(['is_active' => true]);
        $admin = AdminUser::factory()->create(array_merge([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
            'email' => 'store@example.test',
        ], $overrides));
        $passkey = $admin->passkeys()->create([
            'name' => 'MacBook',
            'credential_id' => 'cred-'.uniqid('remote', true),
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

        return [$admin, $passkey];
    }
}
