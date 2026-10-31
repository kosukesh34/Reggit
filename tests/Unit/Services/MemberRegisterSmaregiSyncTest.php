<?php

namespace Tests\Unit\Services;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\CouponIssueService;
use App\Services\Member\MemberRegistrar;
use App\Services\Smaregi\SmaregiApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MemberRegisterSmaregiSyncTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Test', 'description' => 'Test', 'is_active' => true]
        );
        config(['smaregi.contract_id' => '']);
    }

    public function test_register_member_スマレジ成功後に_d_bへ保存しcustomer_idを保持すること(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('sb_test_contract');
        $smaregiApi->shouldReceive('clearLastErrorDetail')->once();
        $smaregiApi->shouldReceive('getCustomerByCode')->once()->andReturn(null);
        $smaregiApi->shouldReceive('getLastHttpStatus')->andReturn(200);
        $smaregiApi->shouldReceive('getLastErrorDetail')->andReturn(null);
        $smaregiApi->shouldReceive('createCustomer')
            ->once()
            ->withArgs(function (string $contractId, array $body): bool {
                return $contractId === 'sb_test_contract'
                    && ($body['lastName'] ?? null) === '山田'
                    && ($body['firstName'] ?? null) === '太郎'
                    && ($body['phoneNumber'] ?? null) === '09012345678'
                    && ($body['postCode'] ?? null) === '1000001'
                    && ($body['address'] ?? null) === '東京都千代田区'
                    && ($body['birthDate'] ?? null) === '1990-01-02'
                    && ($body['sex'] ?? null) === '1'
                    && ($body['mailReceiveFlag'] ?? null) === '1'
                    && isset($body['entryDate']);
            })
            ->andReturn(['customerId' => 'smaregi-123']);

        $couponIssue = Mockery::mock(CouponIssueService::class);
        $couponIssue->shouldReceive('issueForFirstRegistration')->once();

        $this->app->instance(SmaregiApiService::class, $smaregiApi);
        $this->app->instance(CouponIssueService::class, $couponIssue);

        $member = app(MemberRegistrar::class)->registerMember($user, [
            'name' => '山田 太郎',
            'kana' => 'ヤマダ タロウ',
            'phone' => '09012345678',
            'postal_code' => '1000001',
            'address' => '東京都千代田区',
            'birthday' => '1990-01-02',
            'sex' => '1',
            'mail_receive_flag' => '1',
        ]);

        $this->assertSame('smaregi-123', $member->smaregi_customer_id);
        $this->assertSame('1', $member->mail_receive_flag);
        $this->assertDatabaseHas('members', [
            'user_id' => $user->id,
            'smaregi_customer_id' => 'smaregi-123',
            'mail_receive_flag' => '1',
        ]);
    }

    public function test_register_member_任意項目をスマレジ自由項目として送ること(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);
        ShopSettingStore::setProfileFieldSettings([
            'smaregi_fields' => [],
            'custom_fields' => [
                [
                    'key' => 'nickname',
                    'label' => 'ニックネーム',
                    'required' => false,
                    'smaregi_no' => 1,
                ],
            ],
        ], (int) $this->project->id);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('sb_test_contract');
        $smaregiApi->shouldReceive('clearLastErrorDetail')->once();
        $smaregiApi->shouldReceive('getCustomerByCode')->once()->andReturn(null);
        $smaregiApi->shouldReceive('getLastHttpStatus')->andReturn(200);
        $smaregiApi->shouldReceive('getLastErrorDetail')->andReturn(null);
        $smaregiApi->shouldReceive('createCustomer')
            ->once()
            ->withArgs(function (string $contractId, array $body): bool {
                return $contractId === 'sb_test_contract'
                    && ($body['customFields'] ?? null) === [
                        ['no' => 1, 'value' => 'たろう'],
                    ];
            })
            ->andReturn(['customerId' => 'smaregi-cf']);

        $couponIssue = Mockery::mock(CouponIssueService::class);
        $couponIssue->shouldReceive('issueForFirstRegistration')->once();

        $this->app->instance(SmaregiApiService::class, $smaregiApi);
        $this->app->instance(CouponIssueService::class, $couponIssue);

        $member = app(MemberRegistrar::class)->registerMember($user, [
            'name' => '山田 太郎',
            'nickname' => 'たろう',
        ]);

        $this->assertSame('smaregi-cf', $member->smaregi_customer_id);
        $this->assertSame(['nickname' => 'たろう'], $member->profile_extra);
    }

    public function test_register_member_スマレジ失敗時は_d_bに会員を作らないこと(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('sb_test_contract');
        $smaregiApi->shouldReceive('clearLastErrorDetail')->once();
        $smaregiApi->shouldReceive('getCustomerByCode')->once()->andReturn(null);
        $smaregiApi->shouldReceive('getLastHttpStatus')->andReturn(200);
        $smaregiApi->shouldReceive('createCustomer')->once()->andReturn(null);
        $smaregiApi->shouldReceive('getLastErrorDetail')->andReturn(null, '[lastKana]の入力内容に誤りがあります');

        $this->app->instance(SmaregiApiService::class, $smaregiApi);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('スマレジへの会員登録に失敗しました');

        try {
            app(MemberRegistrar::class)->registerMember($user, [
                'name' => '山田 太郎',
                'kana' => '山田太郎',
                'phone' => '09012345678',
            ]);
        } finally {
            $this->assertDatabaseMissing('members', ['user_id' => $user->id]);
            $this->assertSame(0, Member::where('user_id', $user->id)->count());
        }
    }

    public function test_register_member_ゲスト自動作成はスマレジ失敗でも採番して会員を作ること(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('sb_test_contract');
        $smaregiApi->shouldReceive('clearLastErrorDetail')->once();
        $smaregiApi->shouldReceive('getCustomerByCode')->once()->andReturn(null);
        $smaregiApi->shouldReceive('getLastHttpStatus')->andReturn(200);
        $smaregiApi->shouldReceive('createCustomer')->once()->andReturn(null);
        $smaregiApi->shouldReceive('getLastErrorDetail')->andReturn(null, 'smaregi down');

        $couponIssue = Mockery::mock(CouponIssueService::class);
        $couponIssue->shouldReceive('issueForFirstRegistration')->once();

        $this->app->instance(SmaregiApiService::class, $smaregiApi);
        $this->app->instance(CouponIssueService::class, $couponIssue);

        $member = app(MemberRegistrar::class)->registerMember($user, [], false);

        $this->assertNull($member->smaregi_customer_id);
        $this->assertNotSame('', trim((string) $member->member_number));
        $this->assertDatabaseHas('members', ['user_id' => $user->id]);
    }

    public function test_register_member_契約未設定ならローカルのみ登録すること(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn(null);
        $smaregiApi->shouldNotReceive('createCustomer');

        $couponIssue = Mockery::mock(CouponIssueService::class);
        $couponIssue->shouldReceive('issueForFirstRegistration')->once();

        $this->app->instance(SmaregiApiService::class, $smaregiApi);
        $this->app->instance(CouponIssueService::class, $couponIssue);

        $member = app(MemberRegistrar::class)->registerMember($user, [
            'name' => '山田 太郎',
        ]);

        $this->assertNull($member->smaregi_customer_id);
        $this->assertDatabaseHas('members', ['user_id' => $user->id]);
        $this->assertSame('bronze', $member->rank);
    }

    public function test_register_member_初期ランクは_resolverと同じ判定になること(): void
    {
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'guest', 'label' => 'ゲスト', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 2, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 3, 'is_active' => true],
        ], (int) $this->project->id);

        $user = User::factory()->create(['project_id' => $this->project->id]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn(null);
        $couponIssue = Mockery::mock(CouponIssueService::class);
        $couponIssue->shouldReceive('issueForFirstRegistration')->once();
        $this->app->instance(SmaregiApiService::class, $smaregiApi);
        $this->app->instance(CouponIssueService::class, $couponIssue);

        $member = app(MemberRegistrar::class)->registerMember($user, [
            'name' => '山田 太郎',
        ]);

        // metric=0 のとき Resolver は min_points=0 の最後（order 大）を採用する
        $this->assertSame('bronze', $member->rank);
    }
}
