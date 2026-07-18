<?php

namespace Tests\Unit\Services;

use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\CouponIssueService;
use App\Services\MemberService;
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

    public function test_register_member_スマレジ成功後にDBへ保存しcustomerIdを保持すること(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('sb_test_contract');
        $smaregiApi->shouldReceive('clearLastErrorDetail')->once();
        $smaregiApi->shouldReceive('createCustomer')
            ->once()
            ->andReturn(['customerId' => 'smaregi-123']);

        $couponIssue = Mockery::mock(CouponIssueService::class);
        $couponIssue->shouldReceive('issueForFirstRegistration')->once();

        $this->app->instance(SmaregiApiService::class, $smaregiApi);
        $this->app->instance(CouponIssueService::class, $couponIssue);

        $member = app(MemberService::class)->registerMember($user, [
            'name' => '山田 太郎',
            'kana' => 'ヤマダ タロウ',
            'phone' => '09012345678',
        ]);

        $this->assertSame('smaregi-123', $member->smaregi_customer_id);
        $this->assertDatabaseHas('members', [
            'user_id' => $user->id,
            'smaregi_customer_id' => 'smaregi-123',
        ]);
    }

    public function test_register_member_スマレジ失敗時はDBに会員を作らないこと(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('sb_test_contract');
        $smaregiApi->shouldReceive('clearLastErrorDetail')->once();
        $smaregiApi->shouldReceive('createCustomer')->once()->andReturn(null);
        $smaregiApi->shouldReceive('getLastErrorDetail')->andReturn('[lastKana]の入力内容に誤りがあります');

        $this->app->instance(SmaregiApiService::class, $smaregiApi);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('スマレジへの会員登録に失敗しました');

        try {
            app(MemberService::class)->registerMember($user, [
                'name' => '山田 太郎',
                'kana' => '山田太郎',
                'phone' => '09012345678',
            ]);
        } finally {
            $this->assertDatabaseMissing('members', ['user_id' => $user->id]);
            $this->assertSame(0, Member::where('user_id', $user->id)->count());
        }
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

        $member = app(MemberService::class)->registerMember($user, [
            'name' => '山田 太郎',
        ]);

        $this->assertNull($member->smaregi_customer_id);
        $this->assertDatabaseHas('members', ['user_id' => $user->id]);
    }
}
