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

class AdminMemberProfileAtomicityTest extends TestCase
{
    use RefreshDatabase;

    private function member(): Member
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id, 'name' => 'Original', 'email' => 'old@example.com']);
        $this->mock(SmaregiApiService::class)->shouldReceive('getContractIdForProject')->andReturnNull();

        return Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id,
            'status' => 'active', 'smaregi_customer_id' => null])->load('user');
    }

    public function test_member_save_failure_rolls_back_user_fields(): void
    {
        $member = $this->member();
        Event::listen('eloquent.updating: '.Member::class, function (): void {
            throw new \RuntimeException('member save failed');
        });
        try {
            app(MemberAdminService::class)->updateMemberProfile($member,
                ['name' => 'Changed', 'email' => 'new@example.com'], ['status' => 'inactive']);
            $this->fail('Member update must fail');
        } catch (\RuntimeException $exception) {
            $this->assertSame('member save failed', $exception->getMessage());
        }

        $this->assertSame('Original', $member->user->fresh()->name);
        $this->assertSame('old@example.com', $member->user->fresh()->email);
        $this->assertSame('active', $member->fresh()->status);
    }

    public function test_success_saves_user_and_member_fields_together(): void
    {
        $member = $this->member();

        app(MemberAdminService::class)->updateMemberProfile($member,
            ['name' => 'Changed', 'email' => 'new@example.com'], ['status' => 'inactive']);

        $this->assertSame('Changed', $member->user->fresh()->name);
        $this->assertSame('new@example.com', $member->user->fresh()->email);
        $this->assertSame('inactive', $member->fresh()->status);
    }

    public function test_stale_member_instance_does_not_update_the_previous_user(): void
    {
        $member = $this->member();
        $replacement = User::factory()->create(['project_id' => $member->project_id, 'name' => 'Replacement']);
        $member->fresh()->update(['user_id' => $replacement->id]);
        try {
            app(MemberAdminService::class)->updateMemberProfile($member, ['name' => 'Changed'], ['status' => 'inactive']);
            $this->fail('Stale member update must be rejected');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('再読み込み', $exception->getMessage());
        }
        $this->assertSame('Original', $member->user->fresh()->name);
        $this->assertSame('Replacement', $replacement->fresh()->name);
        $this->assertSame('active', $member->fresh()->status);
    }

    public function test_stale_member_with_deleted_previous_user_is_rejected_before_saving(): void
    {
        $member = $this->member();
        $replacement = User::factory()->create(['project_id' => $member->project_id, 'name' => 'Replacement']);
        $member->fresh()->update(['user_id' => $replacement->id]);
        $member->user->delete();
        try {
            app(MemberAdminService::class)->updateMemberProfile($member, [], ['status' => 'inactive']);
            $this->fail('Stale member update must be rejected');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('再読み込み', $exception->getMessage());
        }
        $this->assertSame('active', $member->fresh()->status);
        $this->assertSame('Replacement', $replacement->fresh()->name);
    }
}
