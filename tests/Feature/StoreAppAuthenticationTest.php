<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\StoreAppCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
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

    #[TestWith(['status', false])]
    #[TestWith(['call', false])]
    #[TestWith(['recall', false])]
    #[TestWith(['enter', false])]
    #[TestWith(['cancel', false])]
    #[TestWith(['status', true])]
    #[TestWith(['call', true])]
    #[TestWith(['recall', true])]
    #[TestWith(['enter', true])]
    #[TestWith(['cancel', true])]
    #[TestWith(['status', false, true])]
    #[TestWith(['call', false, true])]
    #[TestWith(['recall', false, true])]
    #[TestWith(['enter', false, true])]
    #[TestWith(['cancel', false, true])]
    public function test_waiting_device_actions_target_the_route_entry(string $action, bool $legacy, bool $numericKey = false): void
    {
        [$project] = $this->createEnrollment('waiting', 'WAITING-ACTIONS');
        if ($numericKey) {
            $project->update(['project_key' => '1']);
        }
        $token = $this->postJson('/api/store-app/login', [
            'enrollment_token' => 'WAITING-ACTIONS', 'app' => 'waiting',
            'device_key' => str_repeat('g', 32),
        ])->assertOk()->json('token');
        $initialStatus = in_array($action, ['recall', 'enter'], true) ? QueueStatus::CALLED : QueueStatus::WAITING;
        $other = QueueEntry::factory()->create(['project_id' => $project->id, 'status' => QueueStatus::WAITING]);
        $entry = QueueEntry::factory()->create(['project_id' => $project->id, 'status' => $initialStatus, 'qr_token' => 'entry-access-token']);
        $base = $legacy ? '/api/queue' : "/api/store-app/waiting/{$project->project_key}/queue";
        $url = $base.'/'.$entry->id.($action === 'status' ? '' : '/'.$action);
        if ($legacy) {
            $url .= '?project_key='.$project->project_key;
            if (in_array($action, ['status', 'cancel'], true)) {
                $url .= '&queue_token='.$entry->qr_token;
            }
        }
        $this->withToken($token)->withHeader('X-Device-Key', str_repeat('g', 32));
        $response = $action === 'status' ? $this->getJson($url) : $this->postJson($url);
        $response->assertOk()->assertJsonPath('queue_entry.id', $entry->id);
        $expected = match ($action) {
            'call', 'recall' => QueueStatus::CALLED,
            'enter' => QueueStatus::ENTERED,
            'cancel' => QueueStatus::CANCELLED,
            default => $initialStatus,
        };
        $this->assertSame($expected, $entry->fresh()->status);
        $this->assertSame(QueueStatus::WAITING, $other->fresh()->status);
    }

    #[TestWith(['status'])]
    #[TestWith(['call'])]
    #[TestWith(['recall'])]
    #[TestWith(['enter'])]
    #[TestWith(['cancel'])]
    public function test_waiting_device_actions_cannot_target_another_project(string $action): void
    {
        [$project] = $this->createEnrollment('waiting', 'WAITING-BOUNDARY');
        $token = $this->postJson('/api/store-app/login', [
            'enrollment_token' => 'WAITING-BOUNDARY', 'app' => 'waiting',
            'device_key' => str_repeat('h', 32),
        ])->assertOk()->json('token');
        $other = Project::factory()->create();
        $entry = QueueEntry::factory()->create(['project_id' => $other->id, 'status' => QueueStatus::WAITING]);
        $url = "/api/store-app/waiting/{$project->project_key}/queue/{$entry->id}".($action === 'status' ? '' : '/'.$action);
        $this->withToken($token)->withHeader('X-Device-Key', str_repeat('h', 32));
        $response = $action === 'status' ? $this->getJson($url) : $this->postJson($url);
        $response->assertNotFound();
        $this->assertSame(QueueStatus::WAITING, $entry->fresh()->status);
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
