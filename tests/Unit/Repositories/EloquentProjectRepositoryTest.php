<?php

namespace Tests\Unit\Repositories;

use App\Models\Project;
use App\Repositories\Eloquent\EloquentProjectRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentProjectRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_active_by_project_key_有効なプロジェクトを返すこと(): void
    {
        $project = Project::factory()->create([
            'project_key' => 'store-a',
            'is_active' => true,
        ]);
        Project::factory()->create([
            'project_key' => 'store-b',
            'is_active' => false,
        ]);

        $repository = new EloquentProjectRepository;
        $found = $repository->findActiveByProjectKey('store-a');

        $this->assertNotNull($found);
        $this->assertSame((int) $project->id, (int) $found->id);
    }

    public function test_find_active_by_project_key_無効または存在しない場合nullを返すこと(): void
    {
        Project::factory()->create([
            'project_key' => 'inactive',
            'is_active' => false,
        ]);

        $repository = new EloquentProjectRepository;

        $this->assertNull($repository->findActiveByProjectKey('inactive'));
        $this->assertNull($repository->findActiveByProjectKey('missing'));
    }

    public function test_find_first_active_有効な先頭プロジェクトを返すこと(): void
    {
        Project::factory()->create(['is_active' => false]);
        Project::factory()->create(['is_active' => true]);

        $expected = Project::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $repository = new EloquentProjectRepository;
        $found = $repository->findFirstActive();

        $this->assertNotNull($found);
        $this->assertNotNull($expected);
        $this->assertSame((int) $expected->id, (int) $found->id);
    }

    public function test_find_first_active_by_ids_名前順の先頭を返すこと(): void
    {
        $projectB = Project::factory()->create(['name' => 'B店', 'is_active' => true]);
        Project::factory()->create(['name' => 'C店', 'is_active' => true]);

        $repository = new EloquentProjectRepository;
        $found = $repository->findFirstActiveByIds([(int) $projectB->id, 9999]);

        $this->assertNotNull($found);
        $this->assertSame((int) $projectB->id, (int) $found->id);
    }
}
