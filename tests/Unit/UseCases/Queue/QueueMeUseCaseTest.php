<?php

namespace Tests\Unit\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\Member;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\User;
use App\UseCases\Queue\Data\QueueMeInput;
use App\UseCases\Queue\QueueMeUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueMeUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_券がないときは待ち人数を返しセッションを忘れること(): void
    {
        $project = $this->createProject();
        $user = $this->createMemberUser($project);

        $result = app(QueueMeUseCase::class)->handle(new QueueMeInput(
            $user,
            (int) $project->id,
            null,
            null,
            null,
        ));

        $this->assertNull($result->queueEntry);
        $this->assertTrue($result->shouldForgetSession);
        $this->assertSame(0, $result->waitingCount);
        $this->assertSame((int) $project->id, $result->sessionProjectId);
    }

    public function test_queue_tokenで同一店舗の券を返すこと(): void
    {
        $project = $this->createProject();
        $user = $this->createMemberUser($project);
        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => null,
            'member_id' => null,
            'status' => QueueStatus::WAITING,
            'qr_token' => 'token-same-store',
        ]);

        $result = app(QueueMeUseCase::class)->handle(new QueueMeInput(
            $user,
            (int) $project->id,
            'token-same-store',
            null,
            null,
        ));

        $this->assertNotNull($result->queueEntry);
        $this->assertSame($entry->id, $result->queueEntry->id);
        $this->assertSame($entry->id, $result->sessionEntryIdToRemember);

        $entry->refresh();
        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame($user->member->id, $entry->member_id);
    }

    public function test_他店舗のqueue_tokenでは券を返さないこと(): void
    {
        $project = $this->createProject();
        $other = Project::create([
            'project_key' => 'other',
            'name' => 'Other Project',
            'description' => 'Test',
            'is_active' => true,
        ]);
        $user = $this->createMemberUser($project);
        QueueEntry::factory()->create([
            'project_id' => $other->id,
            'user_id' => null,
            'member_id' => null,
            'status' => QueueStatus::WAITING,
            'qr_token' => 'token-other-store',
        ]);

        $result = app(QueueMeUseCase::class)->handle(new QueueMeInput(
            $user,
            (int) $project->id,
            'token-other-store',
            null,
            null,
        ));

        $this->assertNull($result->queueEntry);
        $this->assertTrue($result->shouldForgetSession);
    }

    public function test_キャンセル済みのtokenでは券を返さないこと(): void
    {
        $project = $this->createProject();
        $user = $this->createMemberUser($project);
        QueueEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => null,
            'member_id' => null,
            'status' => QueueStatus::CANCELLED,
            'qr_token' => 'token-cancelled',
        ]);

        $result = app(QueueMeUseCase::class)->handle(new QueueMeInput(
            $user,
            (int) $project->id,
            'token-cancelled',
            null,
            null,
        ));

        $this->assertNull($result->queueEntry);
    }

    public function test_セッションのentry_idで券を返すこと(): void
    {
        $project = $this->createProject();
        $user = $this->createMemberUser($project);
        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_id' => $user->member->id,
            'status' => QueueStatus::WAITING,
        ]);

        $result = app(QueueMeUseCase::class)->handle(new QueueMeInput(
            $user,
            (int) $project->id,
            null,
            null,
            (int) $entry->id,
        ));

        $this->assertNotNull($result->queueEntry);
        $this->assertSame($entry->id, $result->queueEntry->id);
    }

    public function test_tokenが無くても自分の待ち券を返すこと(): void
    {
        $project = $this->createProject();
        $user = $this->createMemberUser($project);
        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_id' => $user->member->id,
            'status' => QueueStatus::CALLED,
        ]);

        $result = app(QueueMeUseCase::class)->handle(new QueueMeInput(
            $user,
            (int) $project->id,
            null,
            null,
            null,
        ));

        $this->assertNotNull($result->queueEntry);
        $this->assertSame($entry->id, $result->queueEntry->id);
    }

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createMemberUser(Project $project): User
    {
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        return $user->fresh(['member']);
    }
}
