<?php

namespace Tests\Feature;

use App\Mail\TestMail;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailTestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_システム管理者はメールテスト画面を表示できること(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        $this->actingAs($admin, 'admin')
            ->get(route('system-admin.mail-test.show'))
            ->assertOk()
            ->assertSee('SMTP テスト送信');
    }

    public function test_店舗管理者はメールテスト画面を表示できないこと(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN]);

        $this->actingAs($admin, 'admin')
            ->get(route('system-admin.mail-test.show'))
            ->assertForbidden();
    }

    public function test_システム管理者はテストメールを送信できること(): void
    {
        Mail::fake();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        $this->actingAs($admin, 'admin')
            ->post(route('system-admin.mail-test.send'), ['to' => 'recipient@example.com'])
            ->assertRedirect(route('system-admin.mail-test.show'))
            ->assertSessionHas('status');

        Mail::assertSent(TestMail::class, static fn (TestMail $mail): bool => $mail->hasTo('recipient@example.com'));
    }

    public function test_不正な宛先ではテストメールを送信しないこと(): void
    {
        Mail::fake();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        $this->actingAs($admin, 'admin')
            ->post(route('system-admin.mail-test.send'), ['to' => 'invalid-address'])
            ->assertSessionHasErrors('to');

        Mail::assertNothingSent();
    }
}
