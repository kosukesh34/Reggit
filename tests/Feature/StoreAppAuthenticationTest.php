<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use App\Models\StoreAppCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreAppAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_time_code_registers_one_kitchen_device_without_expiration(): void
    {
        [$project, $admin] = $this->createEnrollment('kitchen', 'ONE-TIME-CODE');
        $login = $this->postJson('/api/store-app/login', [
            'enrollment_token' => 'ONE-TIME-CODE', 'app' => 'kitchen',
            'device_name' => 'Test iPad', 'device_key' => str_repeat('a', 32),
        ])->assertOk()->assertJsonPath('project.key', $project->project_key);

        $token = $login->json('token');
        $this->withToken($token)->withHeader('X-Device-Key', str_repeat('a', 32))
            ->getJson("/api/kitchen/{$project->project_key}/orders")->assertOk();
        $this->withToken($token)->withHeader('X-Device-Key', str_repeat('b', 32))
            ->getJson("/api/kitchen/{$project->project_key}/orders")->assertUnauthorized();
        $this->postJson('/api/store-app/login', [
            'enrollment_token' => 'ONE-TIME-CODE', 'app' => 'kitchen',
            'device_key' => str_repeat('a', 32),
        ])->assertUnprocessable();
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $admin->id,
            'name' => "store-app:kitchen:{$project->id}:Test iPad",
            'expires_at' => null,
        ]);
    }

    public function test_token_is_scoped_to_its_app_and_project(): void
    {
        [$project] = $this->createEnrollment('waiting', 'WAITING-CODE');
        $other = Project::factory()->create(['project_key' => 'other-store']);
        $token = $this->postJson('/api/store-app/login', [
            'enrollment_token' => 'WAITING-CODE', 'app' => 'waiting',
            'device_key' => str_repeat('c', 32),
        ])->assertOk()->json('token');

        $this->withToken($token)->withHeader('X-Device-Key', str_repeat('c', 32))
            ->getJson("/api/store-app/waiting/{$project->project_key}/queue/list")->assertOk();
        $this->withToken($token)->withHeader('X-Device-Key', str_repeat('c', 32))
            ->getJson("/api/kitchen/{$project->project_key}/orders")->assertUnauthorized();
        $this->withToken($token)->withHeader('X-Device-Key', str_repeat('c', 32))
            ->getJson("/api/store-app/waiting/{$other->project_key}/queue/list")->assertForbidden();
    }

    public function test_code_for_another_app_cannot_be_claimed(): void
    {
        $this->createEnrollment('kitchen', 'KITCHEN-CODE');
        $this->postJson('/api/store-app/login', [
            'enrollment_token' => 'KITCHEN-CODE', 'app' => 'waiting',
            'device_key' => str_repeat('d', 32),
        ])->assertUnprocessable();
    }

    public function test_only_authenticated_waiting_device_can_register_non_members(): void
    {
        [$project] = $this->createEnrollment('waiting', 'WAITING-CODE');
        ShopSettingStore::setValue('queue_require_membership', true, 'boolean', $project->id);
        $this->withHeader('X-Queue-Client', 'waiting-system')
            ->postJson("/api/store-app/waiting/{$project->project_key}/queue", ['party_size' => 1])->assertUnauthorized();

        $token = $this->postJson('/api/store-app/login', [
            'enrollment_token' => 'WAITING-CODE', 'app' => 'waiting',
            'device_key' => str_repeat('e', 32),
        ])->assertOk()->json('token');
        $this->withToken($token)->withHeader('X-Device-Key', str_repeat('f', 32))
            ->postJson("/api/store-app/waiting/{$project->project_key}/queue", ['party_size' => 1])->assertUnauthorized();
        $this->withToken($token)->withHeader('X-Device-Key', str_repeat('e', 32))
            ->postJson("/api/store-app/waiting/{$project->project_key}/queue", ['party_size' => 1])->assertCreated();
        $this->assertDatabaseCount('queue_entries', 1);
        $this->assertDatabaseHas('queue_entries', ['project_id' => $project->id, 'member_id' => null]);
    }

    private function createEnrollment(string $app, string $plainToken): array
    {
        $project = Project::factory()->create(['project_key' => 'store-'.$app]);
        $admin = AdminUser::factory()->create(['project_id' => $project->id, 'role' => AdminUser::ROLE_ADMIN]);
        StoreAppCredential::query()->create([
            'project_id' => $project->id, 'admin_user_id' => $admin->id, 'app' => $app,
            'enrollment_token_hash' => hash('sha256', $plainToken), 'enrollment_created_at' => now(),
        ]);

        return [$project, $admin];
    }
}
