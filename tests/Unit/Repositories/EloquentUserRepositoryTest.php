<?php

namespace Tests\Unit\Repositories;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_by_id_and_project_同一プロジェクトのユーザーを返すこと(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        User::factory()->create(['project_id' => $otherProject->id]);

        $repository = new EloquentUserRepository;
        $found = $repository->findByIdAndProject((int) $user->id, (int) $project->id);

        $this->assertNotNull($found);
        $this->assertSame((int) $user->id, (int) $found->id);
    }

    public function test_find_by_line_user_id_and_project_line_user_idで検索できること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U00000000000000000000000000000001',
        ]);

        $repository = new EloquentUserRepository;
        $found = $repository->findByLineUserIdAndProject('U00000000000000000000000000000001', (int) $project->id);

        $this->assertNotNull($found);
        $this->assertSame((int) $user->id, (int) $found->id);
    }
}
