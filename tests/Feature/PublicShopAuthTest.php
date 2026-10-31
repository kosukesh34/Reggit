<?php

namespace Tests\Feature;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Product;
use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Member\Data\IssuedTransferCode;
use App\Services\OrderService;
use App\UseCases\Member\IssueMemberTransferCodeUseCase;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PublicShopAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_ショップ未有効時は404になること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);

        $this->get(route('shop.show', ['project_key' => $project->project_key]))
            ->assertNotFound();
    }

    public function test_ショップ画面とログイン画面が開けること(): void
    {
        $project = $this->enableShop();

        ShopSettingStore::setValue('shop_hero_headline', 'サンプルショップ見出し', 'string', $project->id);

        $this->get(route('shop.show', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee('サンプルショップ見出し');
        $this->get(route('shop.login', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee('LINEでログイン')
            ->assertSee('メールでログインリンク')
            ->assertSee('アプリでログイン')
            ->assertSee('パスキーでログイン')
            ->assertSee('新規登録');
        $this->get(route('shop.login', ['project_key' => $project->project_key, 'mode' => 'register']))
            ->assertOk()
            ->assertDontSee('LINEで')
            ->assertSee('メールで新規登録')
            ->assertSee('アプリでログイン')
            ->assertDontSee('パスキーでログイン');
    }

    public function test_マジックリンク消費後はintendedへ戻ること(): void
    {
        Mail::fake();
        $project = $this->enableShop();
        $email = 'return@example.com';
        $shopPath = parse_url(route('shop.show', ['project_key' => $project->project_key]), PHP_URL_PATH);

        $this->get(route('shop.login', [
            'project_key' => $project->project_key,
            'intended' => $shopPath,
        ]))->assertOk();

        $this->post(route('shop.email.send', ['project_key' => $project->project_key]), [
            'email' => $email,
            'mode' => 'register',
        ])->assertRedirect()->assertSessionHas('success');

        $url = URL::temporarySignedRoute(
            'shop.email.consume',
            now()->addMinutes(30),
            ['project_key' => $project->project_key, 'email' => $email],
        );
        $this->get($url)->assertRedirect($shopPath);
        $this->assertAuthenticatedAs(
            User::query()->where('project_id', $project->id)->where('email', $email)->first(),
            'web',
        );
    }

    public function test_マジックリンクでログインできること(): void
    {
        Mail::fake();
        $project = $this->enableShop();
        $email = 'buyer@example.com';

        $this->post(route('shop.email.send', ['project_key' => $project->project_key]), [
            'email' => $email,
        ])->assertRedirect();

        $url = URL::temporarySignedRoute(
            'shop.email.consume',
            now()->addMinutes(30),
            ['project_key' => $project->project_key, 'email' => $email],
        );
        $this->get($url)->assertRedirect(route('shop.show', ['project_key' => $project->project_key]));
        $this->assertAuthenticatedAs(
            User::query()->where('project_id', $project->id)->where('email', $email)->first(),
            'web',
        );
    }

    public function test_未ログインのゲストでは配送注文できないこと(): void
    {
        $project = $this->enableShop();
        ProjectPaymentSetting::create([
            'project_id' => $project->id,
            'account_id' => 'acct_test',
            'publishable_key' => 'pk_test',
            'secret_key' => 'sk_test',
            'webhook_secret' => 'whsec_test',
            'methods' => ['card'],
        ]);
        $guest = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-abc',
            'email' => null,
            'email_verified_at' => null,
        ]);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'price' => 1000,
            'is_available' => true,
            'stock' => 10,
        ]);

        $this->expectException(OrderOperationException::class);
        app(OrderService::class)->createOrder([
            'order_type' => 'shipping',
            'payment_method' => 'stripe',
            'payment_brand' => 'card',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'shipping_name' => '配送太郎',
            'shipping_phone' => '09011112222',
            'shipping_postal_code' => '1000001',
            'shipping_prefecture' => '東京都',
            'shipping_address' => '千代田区1-1',
        ], $guest);
    }

    public function test_独自ドメインでショップが開けること(): void
    {
        $project = $this->enableShop();
        $project->custom_shop_domain = 'shop.test';
        $project->save();

        ShopSettingStore::setValue('shop_hero_headline', '独自ドメインショップ', 'string', $project->id);

        $this->get('http://shop.test/shop')->assertOk()->assertSee('独自ドメインショップ');
        $this->get('http://shop.test/')->assertRedirect('/shop');
        $this->get('http://other.test/shop')->assertNotFound();
    }

    public function test_セッションログイン後は会員として解決されること(): void
    {
        $project = $this->enableShop();
        $user = User::factory()->create([
            'project_id' => $project->id,
            'email' => 'session@example.com',
            'email_verified_at' => now(),
            'line_user_id' => 'U'.str_repeat('a', 32),
        ]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'web')
            ->get(route('shop.show', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee('session@example.com')
            ->assertSee('"hasPasskey":false', false);
    }

    public function test_web新規ユーザーは必須項目を入力して会員登録してからショップを使うこと(): void
    {
        config(['smaregi.contract_id' => '']);
        $project = $this->enableShop();
        ShopSettingStore::setValue('member_registration_mode', 'force', 'string', $project->id);
        $email = 'new-web@example.com';
        $url = URL::temporarySignedRoute(
            'shop.email.consume',
            now()->addMinutes(30),
            ['project_key' => $project->project_key, 'email' => $email],
        );
        $this->get($url)->assertRedirect();
        $user = User::query()->where('project_id', $project->id)->where('email', $email)->firstOrFail();
        $this->assertNull($user->member);
        $registerUrl = route('shop.member.register', ['project_key' => $project->project_key]);

        $this->get(route('shop.show', ['project_key' => $project->project_key]))->assertRedirect($registerUrl);
        $this->get($registerUrl)->assertOk()->assertSee('会員登録')->assertSee('name="phone"', false);

        $storeUrl = route('shop.member.register.store', ['project_key' => $project->project_key]);
        $this->from($registerUrl)->post($storeUrl, ['name' => 'Web 太郎', 'notification_preference' => 'all'])
            ->assertRedirect($registerUrl)
            ->assertSessionHasErrors('phone');
        $this->assertNull($user->fresh()->member);

        $this->post($storeUrl, [
            'name' => 'Web 太郎',
            'phone' => '09012345678',
            'email' => 'other@example.com',
            'notification_preference' => 'all',
        ])->assertRedirect(route('shop.show', ['project_key' => $project->project_key]));

        $user = $user->fresh();
        $this->assertNotNull($user->member);
        $this->assertSame('09012345678', $user->phone);
        $this->assertSame($email, $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->get(route('shop.show', ['project_key' => $project->project_key]))->assertOk();
    }

    public function test_アプリ引き継ぎコードでショップにログインでき端末は残ること(): void
    {
        $project = $this->enableShop();
        $source = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('b', 32),
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $source->id,
        ]);
        UserDevice::create([
            'project_id' => $project->id,
            'user_id' => $source->id,
            'platform' => 'ios',
            'device_identifier' => 'shop-app-login-device',
        ]);
        $issued = $this->issueTransferCode($source);
        $shopPath = parse_url(route('shop.show', ['project_key' => $project->project_key]), PHP_URL_PATH);

        $this->get(route('shop.login', [
            'project_key' => $project->project_key,
            'intended' => $shopPath,
        ]))->assertOk();

        $this->post(route('shop.app.login', ['project_key' => $project->project_key]), [
            'member_id' => $issued->memberId,
            'code' => $issued->code,
        ])->assertRedirect($shopPath);

        $this->assertAuthenticatedAs($source->fresh(), 'web');
        $this->assertSame($member->id, (int) $source->fresh()->member->id);
        $this->assertDatabaseHas('user_devices', [
            'user_id' => $source->id,
            'device_identifier' => 'shop-app-login-device',
        ]);
    }

    public function test_不正なアプリコードではログインできないこと(): void
    {
        $project = $this->enableShop();
        $source = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('c', 32),
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $source->id,
        ]);
        $this->issueTransferCode($source);
        $usersBefore = User::withTrashed()->count();

        $this->post(route('shop.app.login', ['project_key' => $project->project_key]), [
            'member_id' => $member->id,
            'code' => 'WRONGCOD',
        ])->assertRedirect()->assertSessionHasErrors('app');

        $this->assertGuest('web');
        $this->assertSame($usersBefore, User::withTrashed()->count());
    }

    public function test_既に別会員でログイン中はアプリログインを拒否すること(): void
    {
        $project = $this->enableShop();
        $source = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('d', 32),
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $source->id,
        ]);
        $issued = $this->issueTransferCode($source);

        $other = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('e', 32),
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $other->id,
        ]);

        $this->actingAs($other, 'web')
            ->post(route('shop.app.login', ['project_key' => $project->project_key]), [
                'member_id' => $issued->memberId,
                'code' => $issued->code,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('app');

        $this->assertAuthenticatedAs($other, 'web');
    }

    public function test_lineログインは登録済み会員のみ許可されること(): void
    {
        $project = $this->enableShop();
        $member = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('f', 32),
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);
        $this->fakeLineProfile('U'.str_repeat('f', 32));

        $this->lineCallback($project)->assertRedirect(route('shop.show', ['project_key' => $project->project_key]));

        $this->assertAuthenticatedAs($member->fresh(), 'web');
    }

    public function test_未登録のlineアカウントではログインもユーザー作成もされないこと(): void
    {
        $project = $this->enableShop();
        $lineUserId = 'U'.str_repeat('1', 32);
        $guest = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-shop-abc',
        ]);
        $usersBefore = User::withTrashed()->count();
        $this->fakeLineProfile($lineUserId);

        $this->lineCallback($project)
            ->assertRedirect(route('shop.login', ['project_key' => $project->project_key]))
            ->assertSessionHasErrors('line');
        $this->assertGuest('web');
        $this->assertSame($usersBefore, User::withTrashed()->count());

        // 会員でないゲストのセッションへ LINE を付け替えないこと
        $this->actingAs($guest, 'web');
        $this->lineCallback($project)->assertSessionHasErrors('line');
        $this->assertSame('guest-shop-abc', $guest->fresh()->line_user_id);

        // LINE は一致しても会員でないユーザーは拒否すること
        $lineOnly = User::factory()->create(['project_id' => $project->id, 'line_user_id' => $lineUserId]);
        $this->lineCallback($project)->assertSessionHasErrors('line');
        $this->assertAuthenticatedAs($guest, 'web');
        $this->assertNotSame($lineOnly->id, auth('web')->id());
    }

    private function fakeLineProfile(string $lineUserId): void
    {
        Http::fake([
            'api.line.me/oauth2/v2.1/token' => Http::response(['access_token' => 'token']),
            'api.line.me/v2/profile' => Http::response(['userId' => $lineUserId, 'displayName' => 'LINE太郎']),
        ]);
    }

    private function lineCallback(Project $project): TestResponse
    {
        return $this->withSession(['shop_line_oauth_state' => 'state'])
            ->get(route('shop.line.callback', [
                'project_key' => $project->project_key,
                'state' => 'state',
                'code' => 'code',
            ]));
    }

    private function issueTransferCode(User $source): IssuedTransferCode
    {
        $previous = AdminContext::getCurrentProject();
        AdminContext::setCurrentProject(Project::query()->findOrFail((int) $source->project_id));
        try {
            return app(IssueMemberTransferCodeUseCase::class)->handle($source, null);
        } finally {
            AdminContext::setCurrentProject($previous);
        }
    }

    private function enableShop(): Project
    {
        $project = Project::factory()->create(['is_active' => true, 'service_status' => 'active']);
        ShopSettingStore::setValue('feature_shop_enabled', true, 'boolean', $project->id);
        ShopSettingStore::setValue('shop_shipping_fee', 300, 'integer', $project->id);

        return $project;
    }
}
