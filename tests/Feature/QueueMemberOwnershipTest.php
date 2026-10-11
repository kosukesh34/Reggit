<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\UseCases\Queue\Data\QueueStoreInput;
use App\UseCases\Queue\QueueStoreUseCase;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class QueueMemberOwnershipTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([true, false])]
    #[TestWith([true, true])]
    #[TestWith([false, false])]
    #[TestWith([false, true])]
    public function test_stale_member_relation_does_not_authorize_or_attach_a_queue_entry(bool $required, bool $deleted): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setValue('queue_require_membership', $required, 'boolean', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U11111111111111111111111111111111']);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $user->load('member');
        if ($deleted) {
            $member->delete();
        } else {
            $newOwner = User::factory()->create(['project_id' => $project->id]);
            $member->forceFill(['user_id' => $newOwner->id])->save();
        }

        $result = app(QueueStoreUseCase::class)->handle(new QueueStoreInput($user, 2, null, null, null, null));

        $this->assertSame($required, $result->requiresMembership);
        if ($required) {
            $this->assertNull($result->entry);
            $this->assertDatabaseCount('queue_entries', 0);
        } else {
            $this->assertNotNull($result->entry);
            $this->assertDatabaseHas('queue_entries', ['project_id' => $project->id, 'user_id' => $user->id, 'member_id' => null]);
            $this->assertDatabaseMissing('queue_entries', ['member_id' => $member->id]);
        }
    }
}
