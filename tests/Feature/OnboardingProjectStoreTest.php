<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use App\Models\SmaregiContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingProjectStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string|bool>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'project_key' => 'store-a',
            'name' => '店舗A',
            'description' => null,
            'is_active' => true,
            'plan' => 'normal',
            'smaregi_contract_id' => 'sb_skx928r2',
            'smaregi_client_id' => 'test_client_id_onboard',
            'smaregi_client_secret' => 'test_client_secret_onboard',
            'admin_name' => '管理者',
            'admin_login_id' => 'store_admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'Password1!',
            'admin_password_confirmation' => 'Password1!',
        ], $overrides);
    }

    public function test_未認証でプロジェクトと管理者を作成できること(): void
    {
        $response = $this->postJson('/api/onboarding/projects', $this->validPayload());

        $response->assertCreated();
        $response->assertJsonPath('project.project_key', 'store-a');
        $response->assertJsonPath('admin.login_id', 'store_admin');
        $response->assertJsonPath('login_url', '/admin/store-a/login');
        $this->assertArrayNotHasKey('smaregi_client_secret', $response->json());
        $this->assertStringNotContainsString('test_client_secret_onboard', $response->getContent());

        $this->assertDatabaseHas('projects', ['project_key' => 'store-a', 'name' => '店舗A']);
        $this->assertDatabaseHas('admin_users', [
            'login_id' => 'store_admin',
            'email' => 'admin@example.com',
            'role' => AdminUser::ROLE_PROJECT_ADMIN,
        ]);

        $project = Project::where('project_key', 'store-a')->firstOrFail();
        $this->assertSame('sb_skx928r2', ShopSettingStore::getValue('smaregi_contract_id', null, (int) $project->id));
        $this->assertSame('test_client_id_onboard', ShopSettingStore::getValue('smaregi_client_id', null, (int) $project->id));
        $this->assertSame('test_client_secret_onboard', ShopSettingStore::getValue('smaregi_client_secret', null, (int) $project->id));
    }

    public function test_必須項目不足なら422を返すこと(): void
    {
        $response = $this->postJson('/api/onboarding/projects', [
            'project_key' => 'store-b',
            'name' => '店舗B',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('projects', ['project_key' => 'store-b']);
    }

    public function test_既存店舗の契約_i_dを付け替えたら旧店舗設定から外れること(): void
    {
        $oldProject = Project::factory()->create([
            'project_key' => 'old-store',
            'is_active' => true,
        ]);
        ShopSettingStore::setValue('smaregi_contract_id', 'sb_shared_contract', 'string', (int) $oldProject->id);
        SmaregiContract::create([
            'contract_id' => 'sb_shared_contract',
            'project_id' => $oldProject->id,
        ]);

        $response = $this->postJson('/api/onboarding/projects', $this->validPayload([
            'project_key' => 'new-store',
            'smaregi_contract_id' => 'sb_shared_contract',
            'admin_login_id' => 'new_store_admin',
            'admin_email' => 'new-admin@example.com',
        ]));

        $response->assertCreated();
        $newProject = Project::where('project_key', 'new-store')->firstOrFail();

        $this->assertSame('sb_shared_contract', ShopSettingStore::getValue('smaregi_contract_id', null, (int) $newProject->id));
        $this->assertTrue(
            ShopSettingStore::getValue('smaregi_contract_id', null, (int) $oldProject->id) === null
            || ShopSettingStore::getValue('smaregi_contract_id', null, (int) $oldProject->id) === ''
        );
        $this->assertDatabaseHas('smaregi_contracts', [
            'contract_id' => 'sb_shared_contract',
            'project_id' => $newProject->id,
        ]);
    }
}
