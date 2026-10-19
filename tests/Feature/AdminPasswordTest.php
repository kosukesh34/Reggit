<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Notifications\AdminPasswordReset;
use App\Support\AdminPasskeyGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdminPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_スタッフが現在のパスワードで変更できること(): void
    {
        $staff = AdminUser::factory()->create(['role' => 'staff', 'password' => 'OriginalPass123']);
        $this->actingAs($staff, 'admin')
            ->get(route('admin.password.edit'))
            ->assertRedirect(route('admin.passkey.settings'));
        $this->actingAs($staff, 'admin')
            ->get(route('admin.passkey.settings'))
            ->assertOk()
            ->assertSee('パスワード変更')
            ->assertSee('ログイン設定');
        $data = ['current_password' => 'incorrect', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'];
        $this->put(route('admin.password.change'), $data)->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('OriginalPass123', $staff->fresh()->password));
        $data['current_password'] = 'OriginalPass123';
        $this->put(route('admin.password.change'), $data)->assertRedirect()->assertSessionHas('status');
        $this->assertTrue(Hash::check('NewPassword123', $staff->fresh()->password));
    }

    public function test_change_未ログインではパスワード変更できないこと(): void
    {
        $this->get(route('admin.password.edit'))->assertRedirect();
        $this->put(route('admin.password.change'), [])->assertRedirect();
    }

    public function test_email_リセットメールに管理画面リンクが含まれ不明メールは開示しないこと(): void
    {
        Notification::fake();
        $staff = AdminUser::factory()->create(['role' => 'staff']);
        $this->post(route('admin.password.email'), ['email' => $staff->email])->assertRedirect()->assertSessionHas('status');
        Notification::assertSentTo($staff, AdminPasswordReset::class, function ($notification) use ($staff) {
            $url = $notification->toMail($staff)->actionUrl;
            $this->assertStringContainsString('/admin/reset-password/', $url);
            $this->assertStringContainsString(urlencode($staff->email), $url);

            return true;
        });
        $this->post(route('admin.password.email'), ['email' => 'missing@example.test'])->assertRedirect()->assertSessionHas('status')->assertSessionHasNoErrors();
        Notification::assertCount(1);
    }

    public function test_reset_有効トークンで一度だけリセットしremember_tokenを更新すること(): void
    {
        $staff = AdminUser::factory()->create(['role' => 'staff', 'remember_token' => 'old-token']);
        $token = Password::broker('admins')->createToken($staff);
        $data = ['email' => $staff->email, 'token' => $token, 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'];
        $this->get(route('admin.password.reset', ['token' => $token, 'email' => $staff->email]))->assertOk();
        $this->post(route('admin.password.update'), $data)->assertRedirect(route('admin.login'));
        $this->assertTrue(Hash::check('NewPassword123', $staff->fresh()->password));
        $this->assertNotEquals('old-token', $staff->fresh()->remember_token);
        $this->post(route('admin.password.update'), $data)->assertSessionHasErrors('email');
    }

    public function test_reset_無効期限切れ他人トークンではリセットできないこと(): void
    {
        $staff = AdminUser::factory()->create(['password' => 'OriginalPass123']);
        $other = AdminUser::factory()->create();
        $token = Password::broker('admins')->createToken($other);
        $data = ['email' => $staff->email, 'token' => $token, 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'];
        $this->post(route('admin.password.update'), $data)->assertSessionHasErrors('email');
        $data['token'] = Password::broker('admins')->createToken($staff);
        $this->travel(61)->minutes();
        $this->post(route('admin.password.update'), $data)->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('OriginalPass123', $staff->fresh()->password));
    }

    public function test_change_他セッションを拒否し現セッションは維持すること(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN, 'password' => 'OriginalPass123']);
        $oldHash = auth('admin')->hashPasswordForCookie($admin->password);
        $this->post('/system-admin/login', ['login_id' => $admin->login_id, 'password' => 'OriginalPass123'])->assertRedirect();
        $this->withSession([AdminPasskeyGate::PASSKEY_VERIFIED => true])
            ->put(route('admin.password.change'), [
                'current_password' => 'OriginalPass123', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
            ])->assertRedirect();
        $this->withSession([AdminPasskeyGate::PASSKEY_VERIFIED => true])
            ->get('/system-admin')
            ->assertRedirect(route('system-admin.dashboard'));
        auth('admin')->forgetUser();
        $this->withSession(['password_hash_admin' => $oldHash])->getJson('/system-admin')->assertUnauthorized();
    }

    public function test_reset_保護リクエスト前でも旧セッションを拒否すること(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN, 'password' => 'OriginalPass123']);
        $this->post('/system-admin/login', ['login_id' => $admin->login_id, 'password' => 'OriginalPass123'])->assertRedirect();
        $token = Password::broker('admins')->createToken($admin);
        $this->post(route('admin.password.update'), [
            'email' => $admin->email, 'token' => $token, 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
        ])->assertRedirect();
        auth('admin')->forgetUser();
        $this->getJson('/system-admin')->assertUnauthorized();
    }

    public function test_session_パスワードバインドなしの旧セッションを拒否すること(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $this->actingAs($admin, 'admin');
        session()->forget('password_hash_admin');
        $this->getJson('/system-admin')->assertUnauthorized();
    }

    public function test_reset_確認入力が必須であること(): void
    {
        $staff = AdminUser::factory()->create();
        $token = Password::broker('admins')->createToken($staff);
        $this->post(route('admin.password.update'), ['email' => $staff->email, 'token' => $token, 'password' => 'NewPassword123', 'password_confirmation' => 'different'])->assertSessionHasErrors('password');
    }
}
