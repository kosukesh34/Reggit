<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\AppleWalletPasskitService;
use App\Services\WalletPassAuthTokenService;
use App\Services\WalletPassService;
use App\Util\AdminContext;
use App\Util\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppleWalletPasskitTenantTest extends TestCase
{
    use RefreshDatabase;

    private const TYPE = 'pass.example.member';

    protected function setUp(): void
    {
        parent::setUp();
        config(['wallet_pass.type_id' => self::TYPE]);
    }

    private function member(Project $project): Member
    {
        $user = User::factory()->create(['project_id' => $project->id, 'name' => $project->name]);

        return Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
    }

    private function token(Member $member): array
    {
        return ['Authorization' => 'ApplePass '.app(WalletPassAuthTokenService::class)->createForMember($member)];
    }

    private function devicePath(Member $member): string
    {
        return '/api/apple-wallet/v1/devices/device-'.$member->id.'/registrations/'.self::TYPE;
    }

    public function test_all_passkit_http_operations_work_without_a_tenant_header_for_each_project(): void
    {
        $expected = [];
        $this->mock(WalletPassService::class)->shouldReceive('generateForMember')->twice()
            ->andReturnUsing(function (Member $resolved) use (&$expected): string {
                [$projectId, $name] = $expected[$resolved->id];
                $this->assertSame($projectId, AdminContext::requireCurrentProjectId());
                $fresh = $resolved->fresh(['user', 'project']);
                $this->assertSame($name, $fresh->user->name);
                $this->assertSame($projectId, (int) $fresh->project->id);

                return 'pass-'.$name;
            });
        foreach (['Brand A', 'Brand B'] as $name) {
            $project = Project::factory()->create(['name' => $name]);
            $member = $this->member($project);
            $expected[$member->id] = [(int) $project->id, $name];
            $serial = 'member-'.$member->id;
            $headers = $this->token($member);
            $path = $this->devicePath($member);
            $this->postJson($path.'/'.$serial, ['pushToken' => 'push'], $headers)->assertCreated();
            $this->getJson($path, $headers)->assertOk()->assertJsonPath('serialNumbers', [$serial]);
            $this->get('/api/apple-wallet/v1/passes/'.self::TYPE.'/'.$serial, $headers)
                ->assertOk()->assertContent('pass-'.$name);
            $this->assertNull(AdminContext::getCurrentProject());
            $this->deleteJson($path.'/'.$serial, [], $headers)->assertOk();
            $this->getJson($path, $headers)->assertUnauthorized();
        }
        $this->assertDatabaseCount('wallet_pass_registrations', 0);
    }

    public function test_another_members_token_and_deleted_member_are_rejected(): void
    {
        $member = $this->member(Project::factory()->create());
        $other = $this->member(Project::factory()->create());
        $path = $this->devicePath($other).'/member-'.$other->id;
        $this->postJson($path, ['pushToken' => 'push'], $this->token($member))->assertUnauthorized();
        $this->get('/api/apple-wallet/v1/passes/'.self::TYPE.'/member-'.$other->id, $this->token($member))->assertUnauthorized();
        $headers = $this->token($other);
        $other->delete();
        $this->postJson($path, ['pushToken' => 'push'], $headers)->assertUnauthorized();
        $this->get('/api/apple-wallet/v1/passes/'.self::TYPE.'/member-'.$other->id, $headers)->assertUnauthorized();
        $this->assertDatabaseCount('wallet_pass_registrations', 0);
    }

    public function test_generation_failure_restores_the_previous_tenant(): void
    {
        $project = Project::factory()->create();
        $member = $this->member($project);
        $previous = Project::factory()->create();
        AdminContext::setCurrentProject($previous);
        app(TenantContext::class)->requireProject(true);
        $this->mock(WalletPassService::class)->shouldReceive('generateForMember')->once()
            ->andReturnUsing(function () use ($project): void {
                $this->assertSame((int) $project->id, AdminContext::requireCurrentProjectId());
                throw new \RuntimeException('Signing unavailable');
            });
        try {
            app(AppleWalletPasskitService::class)->generatePassContent($member);
            $this->fail('Expected generation failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Signing unavailable', $exception->getMessage());
        }
        $this->assertSame((int) $previous->id, AdminContext::requireCurrentProjectId());
        $this->assertTrue(app(TenantContext::class)->requiresProject());
    }
}
