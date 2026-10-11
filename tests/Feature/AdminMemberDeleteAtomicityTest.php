<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\Admin\MemberAdminService;
use App\Services\Smaregi\SmaregiApiService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminMemberDeleteAtomicityTest extends TestCase
{
    use RefreshDatabase;

    private function member(): Member
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U-original']);

        return Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id,
            'smaregi_customer_id' => null])->load('user');
    }

    public function test_user_delete_failure_rolls_back_member_line_id_and_token_cleanup(): void
    {
        $member = $this->member();
        $token = $member->user->createToken('review')->accessToken;
        $this->mock(SmaregiApiService::class)->shouldReceive('getContractIdForProject')->andReturnNull();
        Event::listen('eloquent.deleting: '.User::class, function (): void {
            throw new \RuntimeException('user delete failed');
        });
        try {
            app(MemberAdminService::class)->delete((int) $member->id, (int) $member->project_id);
            $this->fail('Delete must fail');
        } catch (\RuntimeException $exception) {
            $this->assertSame('user delete failed', $exception->getMessage());
        }

        $this->assertNull(Member::withTrashed()->findOrFail($member->id)->deleted_at);
        $this->assertNull(User::withTrashed()->findOrFail($member->user_id)->deleted_at);
        $this->assertSame('U-original', $member->user->fresh()->line_user_id);
        $this->assertNotNull($token->fresh());
    }

    public function test_remote_delete_failure_keeps_local_member_and_user(): void
    {
        $member = $this->member();
        $member->update(['smaregi_customer_id' => '42']);
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('deleteCustomer')->once()->with('contract', '42')->andReturnNull();
        try {
            app(MemberAdminService::class)->delete((int) $member->id, (int) $member->project_id);
            $this->fail('Remote failure must reject delete');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('スマレジの会員削除に失敗', $exception->getMessage());
        }
        $this->assertNotNull($member->fresh());
        $this->assertSame('U-original', $member->user->fresh()->line_user_id);
    }

    public function test_customer_relinked_during_remote_delete_is_not_deleted_locally(): void
    {
        $member = $this->member();
        $member->update(['smaregi_customer_id' => '42']);
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('deleteCustomer')->once()->with('contract', '42')->andReturnUsing(function () use ($member): array {
            $member->fresh()->update(['smaregi_customer_id' => '43']);

            return [];
        });
        try {
            app(MemberAdminService::class)->delete((int) $member->id, (int) $member->project_id);
            $this->fail('Stale delete must be rejected');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('再読み込み', $exception->getMessage());
        }
        $this->assertSame('43', $member->fresh()->smaregi_customer_id);
        $this->assertSame('U-original', $member->user->fresh()->line_user_id);
    }

    public function test_owner_changed_during_remote_delete_does_not_delete_either_user(): void
    {
        $member = $this->member();
        $member->update(['smaregi_customer_id' => '42']);
        $replacement = User::factory()->create(['project_id' => $member->project_id]);
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('deleteCustomer')->once()->with('contract', '42')->andReturnUsing(function () use ($member, $replacement): array {
            $member->fresh()->update(['user_id' => $replacement->id]);

            return [];
        });
        try {
            app(MemberAdminService::class)->delete((int) $member->id, (int) $member->project_id);
            $this->fail('Stale owner must be rejected');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('再読み込み', $exception->getMessage());
        }
        $this->assertSame((int) $replacement->id, (int) $member->fresh()->user_id);
        $this->assertSame('U-original', $member->user->fresh()->line_user_id);
        $this->assertNotNull($replacement->fresh());
    }

    public function test_local_failure_after_remote_delete_can_be_retried(): void
    {
        $member = $this->member();
        $member->update(['smaregi_customer_id' => '42']);
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        // The API adapter also returns [] when the customer was already deleted (404).
        $api->shouldReceive('deleteCustomer')->twice()->with('contract', '42')->andReturn([]);
        $attempts = 0;
        Event::listen('eloquent.deleting: '.User::class, function () use (&$attempts): void {
            if (++$attempts === 1) {
                throw new \RuntimeException('temporary local failure');
            }
        });
        try {
            app(MemberAdminService::class)->delete((int) $member->id, (int) $member->project_id);
            $this->fail('First local delete must fail');
        } catch (\RuntimeException $exception) {
            $this->assertSame('temporary local failure', $exception->getMessage());
        }
        $this->assertNotNull($member->fresh());
        $this->assertSame('U-original', $member->user->fresh()->line_user_id);

        app(MemberAdminService::class)->delete((int) $member->id, (int) $member->project_id);

        $this->assertNotNull(Member::withTrashed()->findOrFail($member->id)->deleted_at);
        $this->assertNotNull(User::withTrashed()->findOrFail($member->user_id)->deleted_at);
        $this->assertSame(2, $attempts);
    }

    public function test_success_deletes_member_user_and_tokens_and_releases_line_id(): void
    {
        $member = $this->member();
        $token = $member->user->createToken('review')->accessToken;
        $this->mock(SmaregiApiService::class)->shouldReceive('getContractIdForProject')->andReturnNull();

        app(MemberAdminService::class)->delete((int) $member->id, (int) $member->project_id);

        $this->assertNotNull(Member::withTrashed()->findOrFail($member->id)->deleted_at);
        $this->assertNotNull(User::withTrashed()->findOrFail($member->user_id)->deleted_at);
        $this->assertSame('withdrawn-'.$member->user_id, User::withTrashed()->findOrFail($member->user_id)->line_user_id);
        $this->assertNull($token->fresh());
    }
}
