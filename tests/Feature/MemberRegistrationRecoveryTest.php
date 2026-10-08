<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberRegistrationAttempt;
use App\Models\Project;
use App\Models\User;
use App\Services\CouponIssueService;
use App\Services\MemberRegistrationRecovery;
use App\Services\MemberService;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class MemberRegistrationRecoveryTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        $this->truncateDatabaseTables();
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    private function user(): User
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);

        return User::factory()->create(['project_id' => $project->id]);
    }

    private function api(): MockInterface
    {
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract')->byDefault();
        $api->shouldReceive('clearLastErrorDetail')->byDefault();
        $api->shouldReceive('getLastHttpStatus')->andReturn(200)->byDefault();
        $api->shouldReceive('getLastErrorDetail')->andReturn(null)->byDefault();
        $this->app->instance(SmaregiApiService::class, $api);

        return $api;
    }

    public function test_remote_creation_survives_local_rollback_and_retry_reuses_it(): void
    {
        $user = $this->user();
        $api = $this->api();
        $remote = null;
        $api->shouldReceive('getCustomerByCode')->twice()->andReturnUsing(function () use (&$remote) {
            return $remote;
        });
        $api->shouldReceive('createCustomer')->once()->andReturnUsing(function ($contract, $body) use (&$remote, $user) {
            $connection = config('database.connections.mysql');
            $pdo = new \PDO('mysql:host='.$connection['host'].';port='.$connection['port'].';dbname='.$connection['database'], $connection['username'], $connection['password']);
            $statement = $pdo->prepare('SELECT member_number FROM member_registration_attempts WHERE user_id = ?');
            $statement->execute([$user->id]);
            $this->assertSame($body['customerCode'], $statement->fetchColumn(), 'Reservation must be committed before remote creation');
            $remote = ['customerId' => 'remote-1', 'customerCode' => $body['customerCode'], 'note2' => $body['note2']];

            return $remote;
        });
        $coupon = Mockery::mock(CouponIssueService::class);
        $calls = 0;
        $coupon->shouldReceive('issueForFirstRegistration')->twice()->andReturnUsing(function () use (&$calls) {
            if (++$calls === 1) {
                throw new \RuntimeException('local rollback');
            }
        });
        $this->app->instance(CouponIssueService::class, $coupon);
        try {
            app(MemberService::class)->registerMember($user, ['name' => 'Test']);
            $this->fail('Expected local rollback');
        } catch (\RuntimeException $exception) {
            $this->assertSame('local rollback', $exception->getMessage());
        }
        $this->assertDatabaseMissing('members', ['user_id' => $user->id]);
        $attempt = MemberRegistrationAttempt::where('user_id', $user->id)->firstOrFail();
        $member = app(MemberService::class)->registerMember($user, ['name' => 'Test']);
        $this->assertSame($attempt->member_number, $member->member_number);
        $this->assertSame('remote-1', $member->smaregi_customer_id);
        $this->assertDatabaseMissing('member_registration_attempts', ['user_id' => $user->id]);
    }

    public function test_foreign_note2_is_preserved_and_never_linked_or_overwritten(): void
    {
        $user = $this->user();
        $api = $this->api();
        $api->shouldReceive('getCustomerByCode')->once()->andReturnUsing(fn ($contract, $code) => ['customerId' => 'foreign', 'customerCode' => $code, 'note2' => 'Existing customer note']);
        $api->shouldNotReceive('createCustomer');
        $api->shouldNotReceive('updateCustomer');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('同じ会員コード');
        try {
            app(MemberService::class)->registerMember($user, ['name' => 'Test']);
        } finally {
            $this->assertDatabaseMissing('members', ['user_id' => $user->id]);
        }
    }

    public function test_optional_registration_recovers_ambiguous_response_without_second_post(): void
    {
        $user = $this->user();
        $api = $this->api();
        $remote = null;
        $api->shouldReceive('getCustomerByCode')->twice()->andReturnUsing(function () use (&$remote) {
            return $remote;
        });
        $api->shouldReceive('createCustomer')->once()->andReturnUsing(function ($contract, $body) use (&$remote) {
            $remote = ['customerId' => 'remote-2', 'customerCode' => $body['customerCode'], 'note2' => $body['note2']];

            return null;
        });
        $member = app(MemberService::class)->registerMember($user, ['name' => 'Test'], false);
        $this->assertNull($member->smaregi_customer_id);
        app(MemberRegistrationRecovery::class)->recoverForMember($member);
        $this->assertSame('remote-2', $member->fresh()->smaregi_customer_id);
        $this->assertDatabaseMissing('member_registration_attempts', ['user_id' => $user->id]);
    }

    public function test_lookup_connection_failure_does_not_create_another_customer(): void
    {
        $user = $this->user();
        $api = $this->api();
        $api->shouldReceive('getCustomerByCode')->once()->andReturn(null);
        $api->shouldReceive('getLastHttpStatus')->andReturn(null);
        $api->shouldReceive('getLastErrorDetail')->andReturn('connection timeout');
        $api->shouldNotReceive('createCustomer');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('登録状態を確認できません');
        app(MemberService::class)->registerMember($user, ['name' => 'Test']);
    }

    public function test_contract_change_preserves_pending_attempt_and_refuses_external_writes(): void
    {
        $user = $this->user();
        $attempt = MemberRegistrationAttempt::create([
            'project_id' => $user->project_id, 'user_id' => $user->id,
            'member_number' => 'reserved', 'contract_id' => 'old-contract',
            'registration_key' => (string) Str::uuid(),
        ]);
        $api = $this->api();
        $api->shouldNotReceive('getCustomerByCode');
        $api->shouldNotReceive('createCustomer');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('契約が変更');
        try {
            app(MemberService::class)->registerMember($user, ['name' => 'Test']);
        } finally {
            $this->assertDatabaseHas('member_registration_attempts', ['id' => $attempt->id, 'contract_id' => 'old-contract']);
        }
    }

    public function test_read_recovery_does_not_create_missing_external_customer(): void
    {
        $user = $this->user();
        $member = Member::factory()->create(['project_id' => $user->project_id, 'user_id' => $user->id, 'member_number' => 'reserved', 'smaregi_customer_id' => null]);
        MemberRegistrationAttempt::create([
            'project_id' => $user->project_id, 'user_id' => $user->id,
            'member_number' => 'reserved', 'contract_id' => 'contract',
            'registration_key' => (string) Str::uuid(),
        ]);
        $api = $this->api();
        $api->shouldReceive('getCustomerByCode')->once()->andReturn(null);
        $api->shouldNotReceive('createCustomer');
        $this->assertFalse(app(SmaregiCustomerPointsInboundSyncService::class)->syncMemberFromSmaregi($member));
        app(MemberRegistrationRecovery::class)->recoverForMember($member);
        $this->assertNull($member->fresh()->smaregi_customer_id);
        $this->assertDatabaseHas('member_registration_attempts', ['user_id' => $user->id]);
    }
}
