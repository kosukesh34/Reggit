<?php

namespace Tests\Feature;

use App\Enums\MemberRegistrationMode;
use App\Models\AdminUser;
use App\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSettingsMemberRegistrationModeTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createAdmin(Project $project): AdminUser
    {
        return AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
    }

    /** @return array<string, array{enabled: string, required: string}> */
    private function defaultSmaregiFieldsPayload(): array
    {
        $fields = [];
        foreach (ShopSettingStore::PROFILE_SMAREGI_FIELDS as $key => $_label) {
            $fields[$key] = [
                'enabled' => '1',
                'required' => in_array($key, ['name', 'phone'], true) ? '1' : '0',
            ];
        }

        return $fields;
    }

    public function test_プロフィール設定画面に未会員時の扱いが表示されること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.profile', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('未会員時の扱い');
        $response->assertSee('そのまま使える');
        $response->assertSee('登録を促す（フォーム表示）');
        $response->assertSee('会員登録を必須にする');
    }

    public function test_会員登録モードをforceで保存できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.profile.update', ['project_key' => $project->project_key]), [
                'member_registration_mode' => MemberRegistrationMode::FORCE,
                'smaregi_fields' => $this->defaultSmaregiFieldsPayload(),
            ]);

        $response->assertRedirect(route('admin.settings.profile', ['project_key' => $project->project_key]));
        $response->assertSessionHas('success');
        $this->assertSame(
            MemberRegistrationMode::FORCE,
            ShopSettingStore::getMemberRegistrationMode((int) $project->id)
        );
    }
}
