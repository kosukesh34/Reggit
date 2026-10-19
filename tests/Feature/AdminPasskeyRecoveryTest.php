<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Notifications\AdminPasskeyRecovery;
use App\Support\AdminPasskeyGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Passkeys\Passkey;
use Tests\TestCase;

class AdminPasskeyRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_確認画面からリカバリメールを送れること(): void
    {
        Notification::fake();
        [$admin] = $this->adminWithPasskey();

        $this->actingAs($admin, 'admin')
            ->withSession([AdminPasskeyGate::PASSWORD_AUTHENTICATED => true])
            ->post(route('admin.passkey.recovery.email'))
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($admin, AdminPasskeyRecovery::class, function (AdminPasskeyRecovery $notification) use ($admin) {
            $url = $notification->recoveryUrl($admin);
            $this->assertStringContainsString('/admin/passkey/recovery/confirm', $url);
            $this->assertStringContainsString('signature=', $url);

            return true;
        });
    }

    public function test_recovery_署名付きリンクでパスキーを消して登録画面へ進むこと(): void
    {
        [$admin, $passkey] = $this->adminWithPasskey();
        $url = URL::temporarySignedRoute(
            'admin.passkey.recovery.confirm',
            now()->addMinutes(60),
            ['admin' => $admin->id, 'hash' => sha1((string) $admin->email)],
        );

        $this->actingAs($admin, 'admin')
            ->withSession([AdminPasskeyGate::PASSWORD_AUTHENTICATED => true])
            ->get($url)
            ->assertRedirect(route('admin.passkey.enroll'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('passkeys', ['id' => $passkey->id]);
        $this->get(route('admin.passkey.enroll'))->assertOk()->assertSee('パスキー登録');
    }

    public function test_recovery_他管理者ログイン中はリンクを拒否すること(): void
    {
        [$admin, $passkey] = $this->adminWithPasskey();
        $other = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $admin->project_id,
        ]);
        $url = URL::temporarySignedRoute(
            'admin.passkey.recovery.confirm',
            now()->addMinutes(60),
            ['admin' => $admin->id, 'hash' => sha1((string) $admin->email)],
        );

        $this->actingAs($other, 'admin')
            ->withSession([AdminPasskeyGate::PASSWORD_AUTHENTICATED => true])
            ->get($url)
            ->assertRedirect(route('admin.passkey.verify'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
    }

    public function test_recovery_期限切れリンクは拒否されること(): void
    {
        [$admin, $passkey] = $this->adminWithPasskey();
        $url = URL::temporarySignedRoute(
            'admin.passkey.recovery.confirm',
            now()->addMinutes(60),
            ['admin' => $admin->id, 'hash' => sha1((string) $admin->email)],
        );

        $this->travel(61)->minutes();

        $this->actingAs($admin, 'admin')
            ->withSession([AdminPasskeyGate::PASSWORD_AUTHENTICATED => true])
            ->get($url)
            ->assertRedirect(route('admin.passkey.verify'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
    }

    /**
     * @return array{0: AdminUser, 1: Passkey}
     */
    private function adminWithPasskey(): array
    {
        $project = Project::factory()->create(['is_active' => true]);
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
            'email' => 'store@example.test',
        ]);
        $passkey = $admin->passkeys()->create([
            'name' => 'Lost Phone',
            'credential_id' => 'cred-'.uniqid('lost', true),
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
