<?php

namespace Tests\Feature;

use App\Enums\LandingInquiryPlan;
use App\Enums\ProjectPlan;
use App\Mail\LandingFormUrlMail;
use App\Mail\LandingReadyMail;
use App\Mail\LandingVerificationMail;
use App\Models\AdminUser;
use App\Models\LandingRegistration;
use App\Models\Project;
use App\UseCases\Landing\Data\LandingRegistrationCompleteView;
use App\UseCases\Landing\Data\LandingRegistrationFormInput;
use App\UseCases\Landing\SubmitLandingRegistrationUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LandingRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_トップから専用のメール認証ページへ進めること(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('landing.register.email.show'), false)
            ->assertSee('name="email"', false)
            ->assertSee('導入登録');

        $this->get(route('landing.register.email.show'))
            ->assertOk()
            ->assertSee('認証メールを送る')
            ->assertSee('name="email"', false);
    }

    public function test_トップのメール入力でも認証メールが送られること(): void
    {
        Mail::fake();

        $this->from('/')
            ->post(route('landing.register.email'), ['email' => 'Shop@Example.com'])
            ->assertRedirect(route('landing.register.email.show'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('landing_registrations', [
            'email' => 'shop@example.com',
            'email_verified_at' => null,
        ]);
        Mail::assertSent(LandingVerificationMail::class);
    }

    public function test_メール入力で認証メールが送られること(): void
    {
        Mail::fake();

        $this->from(route('landing.register.email.show'))
            ->post(route('landing.register.email'), ['email' => 'Shop@Example.com'])
            ->assertRedirect(route('landing.register.email.show'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('landing_registrations', [
            'email' => 'shop@example.com',
            'email_verified_at' => null,
        ]);
        Mail::assertSent(LandingVerificationMail::class, static fn (LandingVerificationMail $mail): bool => $mail->hasTo('shop@example.com'));
        Mail::assertNotSent(LandingFormUrlMail::class);
    }

    public function test_不正なメールでは送信しないこと(): void
    {
        Mail::fake();

        $this->from(route('landing.register.email.show'))
            ->post(route('landing.register.email'), ['email' => 'invalid'])
            ->assertRedirect(route('landing.register.email.show'))
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('landing_registrations', 0);
    }

    public function test_認証リンクでフォーム案内メールが送られること(): void
    {
        Mail::fake();
        $registration = LandingRegistration::query()->create(['email' => 'shop@example.com']);
        $verifyUrl = URL::temporarySignedRoute('landing.register.verify', now()->addHour(), [
            'registration' => $registration->id,
        ]);

        $this->followingRedirects()
            ->get($verifyUrl)
            ->assertOk()
            ->assertSee('入力フォーム')
            ->assertSee('shop@example.com')
            ->assertSee('認証が完了しました');

        $registration->refresh();
        $this->assertNotNull($registration->email_verified_at);
        $this->assertNotNull($registration->form_url_sent_at);
        Mail::assertSent(LandingFormUrlMail::class, static fn (LandingFormUrlMail $mail): bool => $mail->hasTo('shop@example.com'));
    }

    public function test_署名なしの認証リンクは拒否されること(): void
    {
        $registration = LandingRegistration::query()->create(['email' => 'shop@example.com']);

        $this->get(route('landing.register.verify', ['registration' => $registration->id]))
            ->assertForbidden();
    }

    public function test_認証後に入力フォームを送信できること(): void
    {
        Mail::fake();
        $registration = LandingRegistration::query()->create([
            'email' => 'shop@example.com',
            'email_verified_at' => now(),
        ]);
        $formUrl = URL::temporarySignedRoute('landing.register.form', now()->addDay(), [
            'registration' => $registration->id,
        ]);
        $submitUrl = URL::temporarySignedRoute('landing.register.form.submit', now()->addDay(), [
            'registration' => $registration->id,
        ]);

        $this->get($formUrl)
            ->assertOk()
            ->assertSee('入力フォーム')
            ->assertSee('shop@example.com')
            ->assertSee('type="radio"', false)
            ->assertSee('LINEミニアプリで会員証・注文・予約・順番待ちを利用します。');

        $this->from($formUrl)
            ->post($submitUrl, [
                'store_name' => 'テスト店舗',
                'contact_name' => '山田太郎',
                'phone' => '03-1234-5678',
                'plan' => LandingInquiryPlan::LINE,
                'message' => '導入相談',
            ])
            ->assertOk()
            ->assertSee('店舗の準備ができました')
            ->assertSee('店舗アプリ（LIFF）')
            ->assertSee('管理画面');
        $this->assertAuthenticated('admin');

        $registration->refresh();
        $this->assertNotNull($registration->submitted_at);
        $this->assertSame($registration->admin_login_id, auth('admin')->user()?->login_id);
        $this->get('/admin/'.$registration->project->project_key.'/stores')->assertOk();
        $this->assertSame('テスト店舗', $registration->store_name);
        $this->assertSame(LandingInquiryPlan::LINE, $registration->plan);
        $this->assertNotNull($registration->project_id);
        $this->assertNotNull($registration->admin_login_id);

        $project = Project::query()->findOrFail($registration->project_id);
        $this->assertSame('テスト店舗', $project->name);
        $this->assertSame(ProjectPlan::NORMAL, $project->plan);
        $this->assertDatabaseHas('admin_users', [
            'email' => 'shop@example.com',
            'login_id' => $registration->admin_login_id,
            'role' => AdminUser::ROLE_PROJECT_ADMIN,
        ]);

        $password = null;
        Mail::assertSent(LandingReadyMail::class, static function (LandingReadyMail $mail) use (&$password): bool {
            $password = $mail->password;

            return $mail->hasTo('shop@example.com');
        });
        $this->assertIsString($password);

        $this->post(route('admin.project.login.submit', ['project_key' => $project->project_key]), [
            'login_id' => $registration->admin_login_id,
            'password' => $password,
        ])->assertRedirect();
        $this->assertAuthenticated('admin');
    }

    public function test_送信済みフォームの再送信では自動ログインしないこと(): void
    {
        Mail::fake();
        $registration = LandingRegistration::query()->create([
            'email' => 'repeat@example.com',
            'email_verified_at' => now(),
        ]);
        $payload = [
            'store_name' => '再送信店舗',
            'contact_name' => '担当者',
            'phone' => '03-1234-5678',
            'plan' => LandingInquiryPlan::LINE,
        ];
        app(SubmitLandingRegistrationUseCase::class)->handle(new LandingRegistrationFormInput($registration, $payload));
        $submitUrl = URL::temporarySignedRoute('landing.register.form.submit', now()->addDay(), [
            'registration' => $registration->id,
        ]);

        $this->post($submitUrl, $payload)->assertOk();

        $this->assertGuest('admin');
        Mail::assertSent(LandingReadyMail::class, 1);
    }

    public function test_stale_registration_submission_does_not_issue_another_project(): void
    {
        Mail::fake();
        $registration = LandingRegistration::query()->create([
            'email' => 'once@example.com',
            'email_verified_at' => now(),
        ]);
        $stale = LandingRegistration::query()->findOrFail($registration->id);
        $payload = [
            'store_name' => 'One shop',
            'contact_name' => 'Owner',
            'phone' => '03-1234-5678',
            'plan' => LandingInquiryPlan::LINE,
        ];
        $useCase = app(SubmitLandingRegistrationUseCase::class);
        $first = $useCase->handle(new LandingRegistrationFormInput($registration, $payload));
        $projectCount = Project::query()->count();
        $adminCount = AdminUser::query()->count();
        $second = $useCase->handle(new LandingRegistrationFormInput($stale, $payload));

        $this->assertInstanceOf(LandingRegistrationCompleteView::class, $first);
        $this->assertInstanceOf(LandingRegistrationCompleteView::class, $second);
        $this->assertNotNull($first->password);
        $this->assertNull($second->password);
        $this->assertSame($first->loginId, $second->loginId);
        $this->assertSame($projectCount, Project::query()->count());
        $this->assertSame($adminCount, AdminUser::query()->count());
        Mail::assertSent(LandingReadyMail::class, 1);
    }

    public function test_ネイティブ希望ならプレミアムプランで発行すること(): void
    {
        Mail::fake();
        $registration = LandingRegistration::query()->create([
            'email' => 'native@example.com',
            'email_verified_at' => now(),
        ]);
        $submitUrl = URL::temporarySignedRoute('landing.register.form.submit', now()->addDay(), [
            'registration' => $registration->id,
        ]);

        $this->post($submitUrl, [
            'store_name' => 'ネイティブ店舗',
            'contact_name' => '佐藤花子',
            'phone' => '090-0000-0000',
            'plan' => LandingInquiryPlan::NATIVE,
        ])->assertOk();

        $registration->refresh();
        $project = Project::query()->findOrFail($registration->project_id);
        $this->assertSame(ProjectPlan::PREMIUM, $project->plan);
    }

    public function test_既存店舗データは導入登録で変わらないこと(): void
    {
        Mail::fake();
        $existing = Project::factory()->create([
            'project_key' => 'other-shop',
            'name' => '既存店舗',
            'is_active' => true,
        ]);
        $registration = LandingRegistration::query()->create([
            'email' => 'newshop@example.com',
            'email_verified_at' => now(),
        ]);
        $submitUrl = URL::temporarySignedRoute('landing.register.form.submit', now()->addDay(), [
            'registration' => $registration->id,
        ]);

        $this->post($submitUrl, [
            'store_name' => '新規店舗',
            'contact_name' => '新規担当',
            'phone' => '06-1111-2222',
            'plan' => LandingInquiryPlan::LINE,
        ])->assertOk();

        $this->assertSame('既存店舗', $existing->fresh()?->name);
        $this->assertNotSame($existing->id, $registration->fresh()?->project_id);
    }

    public function test_既存管理者メールでは店舗を発行しないこと(): void
    {
        Mail::fake();
        $project = Project::factory()->create(['project_key' => 'taken-shop']);
        AdminUser::factory()->create([
            'email' => 'shop@example.com',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $registration = LandingRegistration::query()->create([
            'email' => 'shop@example.com',
            'email_verified_at' => now(),
        ]);
        $formUrl = URL::temporarySignedRoute('landing.register.form', now()->addDay(), [
            'registration' => $registration->id,
        ]);
        $submitUrl = URL::temporarySignedRoute('landing.register.form.submit', now()->addDay(), [
            'registration' => $registration->id,
        ]);

        $this->from($formUrl)
            ->post($submitUrl, [
                'store_name' => 'テスト店舗',
                'contact_name' => '山田太郎',
                'phone' => '03-1234-5678',
                'plan' => LandingInquiryPlan::LINE,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertNull($registration->fresh()?->submitted_at);
        Mail::assertNotSent(LandingReadyMail::class);
    }

    public function test_未認証では入力フォームを開けないこと(): void
    {
        $registration = LandingRegistration::query()->create(['email' => 'shop@example.com']);
        $formUrl = URL::temporarySignedRoute('landing.register.form', now()->addDay(), [
            'registration' => $registration->id,
        ]);

        $this->get($formUrl)->assertForbidden();
    }

    public function test_認証済みなら同じメール再送でフォーム案内が送られること(): void
    {
        Mail::fake();
        LandingRegistration::query()->create([
            'email' => 'shop@example.com',
            'email_verified_at' => now(),
        ]);

        $this->from(route('landing.register.email.show'))
            ->post(route('landing.register.email'), ['email' => 'shop@example.com'])
            ->assertRedirect(route('landing.register.email.show'));

        Mail::assertSent(LandingFormUrlMail::class);
        Mail::assertNotSent(LandingVerificationMail::class);
    }

    public function test_送信済みメールは再送しないこと(): void
    {
        Mail::fake();
        LandingRegistration::query()->create([
            'email' => 'shop@example.com',
            'email_verified_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->from(route('landing.register.email.show'))
            ->post(route('landing.register.email'), ['email' => 'shop@example.com'])
            ->assertRedirect(route('landing.register.email.show'))
            ->assertSessionHas('status', 'このメールアドレスは登録済みです。');

        Mail::assertNothingSent();
    }
}
