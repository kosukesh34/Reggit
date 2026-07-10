<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Models\UserDevice;
use App\Models\Project;
use App\Repositories\Eloquent\EloquentUserDeviceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentUserDeviceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_findLatestByProjectAndIdentifier_最終利用が新しい端末を返すこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        UserDevice::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'device_identifier' => 'device-1',
            'platform' => 'ios',
            'last_used_at' => now()->subDay(),
        ]);
        $latest = UserDevice::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'device_identifier' => 'device-1',
            'platform' => 'ios',
            'last_used_at' => now(),
        ]);

        $repository = new EloquentUserDeviceRepository;
        $found = $repository->findLatestByProjectAndIdentifier((int) $project->id, 'device-1');

        $this->assertNotNull($found);
        $this->assertSame((int) $latest->id, (int) $found->id);
    }
}
