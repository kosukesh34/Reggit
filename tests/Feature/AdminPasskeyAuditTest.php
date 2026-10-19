<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Project;
use App\Notifications\AdminPasskeyRecovery;
use App\Support\AdminPasskeyGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Passkeys\Events\PasskeyDeleted;
use Laravel\Passkeys\Events\PasskeyRegistered;
use Laravel\Passkeys\Passkey;
use Tests\TestCase;

class AdminPasskeyAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_パスキー登録と削除が監査ログに残ること(): void
    {
        [$admin, $passkey] = $this->adminWithPasskey('会社PC');

        event(new PasskeyRegistered($admin, $passkey));
        $registered = AdminAuditLog::query()->latest('id')->first();
        $this->assertNotNull($registered);
        $this->assertSame('パスキーを登録', $registered->getActionLabel());
        $this->assertSame('パスキー（会社PC）', $registered->getTargetLabel());
        $this->assertStringContainsString('端末名', $registered->getChangedFieldsLabel());

        event(new PasskeyDeleted($admin, $passkey));
        $deleted = AdminAuditLog::query()->latest('id')->first();
        $this->assertNotNull($deleted);
        $this->assertSame('パスキーを削除', $deleted->getActionLabel());
        $this->assertSame('パスキー（会社PC）', $deleted->getTargetLabel());
    }

    public function test_audit_リカバリメール送信と確認が監査に残ること(): void
    {
        Notification::fake();
        [$admin, $passkey] = $this->adminWithPasskey();

        $this->actingAs($admin, 'admin')
            ->withSession([AdminPasskeyGate::PASSWORD_AUTHENTICATED => true])
            ->post(route('admin.passkey.recovery.email'))
            ->assertRedirect();

        $emailLog = AdminAuditLog::query()->where('payload->event', 'passkey_recovery_email_sent')->first();
        $this->assertNotNull($emailLog);
        $this->assertSame('パスキー再登録メールを送信', $emailLog->getActionLabel());

        $url = URL::temporarySignedRoute(
            'admin.passkey.recovery.confirm',
            now()->addMinutes(60),
            ['admin' => $admin->id, 'hash' => sha1((string) $admin->email)],
        );
        $this->actingAs($admin, 'admin')
            ->withSession([AdminPasskeyGate::PASSWORD_AUTHENTICATED => true])
            ->get($url)
            ->assertRedirect(route('admin.passkey.enroll'));

        $done = AdminAuditLog::query()->where('payload->event', 'passkey_recovery_completed')->first();
        $this->assertNotNull($done);
        $this->assertSame('パスキーを再登録準備（既存を無効化）', $done->getActionLabel());
        $this->assertDatabaseMissing('passkeys', ['id' => $passkey->id]);
        Notification::assertSentTo($admin, AdminPasskeyRecovery::class);
    }

    public function test_audit_遠隔登録リンク発行が監査に残ること(): void
    {
        [$admin] = $this->adminWithPasskey();

        $this->actingAs($admin, 'admin')
            ->withSession([
                AdminPasskeyGate::PASSWORD_AUTHENTICATED => true,
                AdminPasskeyGate::PASSKEY_VERIFIED => true,
            ])
            ->post(route('admin.passkey.remote-enroll.create'))
            ->assertRedirect();

        $log = AdminAuditLog::query()->where('payload->event', 'passkey_remote_enroll_link_issued')->first();
        $this->assertNotNull($log);
        $this->assertSame('パスキー追加用リンクを発行', $log->getActionLabel());
        $this->get('/system-admin/audit-logs')
            ->assertOk()
            ->assertSee('パスキー追加用リンクを発行');
    }

    /**
     * @return array{0: AdminUser, 1: Passkey}
     */
    private function adminWithPasskey(string $name = 'MacBook'): array
    {
        $project = Project::factory()->create(['is_active' => true]);
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => null,
            'email' => 'audit-passkey@example.test',
        ]);
        $passkey = $admin->passkeys()->create([
            'name' => $name,
            'credential_id' => 'cred-'.uniqid('audit', true),
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
