<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\Store;
use App\Support\AdminPasskeyGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminStaffAccessTest extends TestCase
{
    use RefreshDatabase;

    private function branch(Project $project): Store
    {
        return Store::create(['project_id' => $project->id, 'store_key' => 'branch-'.Store::count(), 'name' => '支店'.Store::count(), 'is_active' => true]);
    }

    private function staffData(array $extra = []): array
    {
        return array_merge([
            'name' => '支店スタッフ', 'login_id' => 'branch-staff', 'email' => 'branch@example.test',
            'password' => 'StrongPass123', 'password_confirmation' => 'StrongPass123',
            'permissions' => ['orders.view'], 'store_assignment' => '1',
        ], $extra);
    }

    public function test_owner_assigns_multiple_branches_and_operation_permissions(): void
    {
        $project = Project::factory()->create();
        $owner = AdminUser::factory()->create(['role' => AdminUser::ROLE_PROJECT_ADMIN, 'project_id' => $project->id]);
        $a = $this->branch($project);
        $b = $this->branch($project);
        $this->actingAs($owner, 'admin')->post(route('admin.settings.staff.store', $project->project_key), $this->staffData(['store_ids' => [$a->id, $b->id]]))->assertRedirect();
        $staff = AdminUser::where('login_id', 'branch-staff')->firstOrFail();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $staff->stores()->pluck('stores.id')->all());
        $this->actingAs($staff, 'admin')->get(route('admin.orders', ['project_key' => $project->project_key, 'store_id' => $a->id]))->assertOk();
        $this->get(route('admin.settings.basic', $project->project_key))->assertForbidden();
    }

    public function test_foreign_and_unassigned_branches_cannot_be_granted(): void
    {
        $project = Project::factory()->create();
        $manager = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $allowed = $this->branch($project);
        $denied = $this->branch($project);
        $foreign = $this->branch(Project::factory()->create());
        $manager->stores()->sync([$allowed->id => ['project_id' => $project->id]]);
        foreach ([$denied, $foreign] as $branch) {
            $this->actingAs($manager, 'admin')->post(route('admin.settings.staff.store', $project->project_key), $this->staffData(['store_ids' => [$branch->id]]))->assertSessionHasErrors('store_ids.0');
        }
        $this->assertDatabaseMissing('admin_users', ['login_id' => 'branch-staff']);
    }

    public function test_manager_cannot_escalate_roles_or_change_staff_at_other_branches(): void
    {
        $project = Project::factory()->create();
        $manager = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $branch = $this->branch($project);
        $otherBranch = $this->branch($project);
        $manager->stores()->sync([$branch->id => ['project_id' => $project->id]]);
        $staff = AdminUser::factory()->create(['role' => AdminUser::ROLE_STAFF, 'project_id' => $project->id]);
        $staff->stores()->sync([$otherBranch->id => ['project_id' => $project->id]]);
        $this->actingAs($manager, 'admin')->post(route('admin.settings.staff.store', $project->project_key), $this->staffData(['role' => AdminUser::ROLE_ADMIN]))->assertSessionHasErrors('role');
        $this->put(route('admin.settings.staff.update', [$project->project_key, $staff->id]), $this->staffData())->assertForbidden();
        $this->get(route('admin.settings.staff', $project->project_key))->assertOk()->assertDontSee($staff->login_id);
    }

    public function test_owner_changes_existing_manager_to_staff_and_revokes_branch_access(): void
    {
        $project = Project::factory()->create();
        $owner = AdminUser::factory()->create(['role' => AdminUser::ROLE_PROJECT_ADMIN, 'project_id' => $project->id]);
        $manager = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $branch = $this->branch($project);
        $manager->stores()->sync([$branch->id => ['project_id' => $project->id]]);
        $this->actingAs($owner, 'admin')->get(route('admin.settings.staff', $project->project_key))->assertOk()->assertSee($manager->login_id)->assertSee($branch->name);
        $this->put(route('admin.settings.staff.update', [$project->project_key, $manager->id]), $this->staffData(['email' => $manager->email, 'role' => 'staff']))->assertRedirect();
        $manager->refresh();
        $this->assertTrue($manager->isStaff());
        $this->assertSame(0, $manager->stores()->count());
        $this->assertTrue(Hash::check('StrongPass123', $manager->password));
        $this->assertTrue($manager->hasPermission('orders.view'));
        $this->assertFalse($manager->hasPermission('orders.update'));
        $this->actingAs($manager, 'admin')->get(route('admin.orders', ['project_key' => $project->project_key, 'store_id' => $branch->id]))->assertForbidden();
    }

    public function test_owner_cannot_create_project_or_system_admin_from_staff_settings(): void
    {
        $project = Project::factory()->create();
        $owner = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        foreach (['project_admin', 'system_admin'] as $role) {
            $this->actingAs($owner, 'admin')->post(route('admin.settings.staff.store', $project->project_key), $this->staffData(['role' => $role]))->assertSessionHasErrors('role');
        }
    }

    public function test_staff_without_dashboard_permission_can_complete_login(): void
    {
        $project = Project::factory()->create();
        $staff = AdminUser::factory()->create(['role' => 'staff', 'project_id' => $project->id, 'password' => 'StrongPass123']);
        $staff->syncPermissions(['orders.view']);
        $this->post(route('admin.project.login.submit', $project->project_key), ['login_id' => $staff->login_id, 'password' => 'StrongPass123'])
            ->assertRedirect(route('admin.passkey.enroll'));
        $this->assertAuthenticatedAs($staff, 'admin');
        $this->assertSame(route('admin.stores.index', $project->project_key), session('url.intended'));
        $this->withSession([
            AdminPasskeyGate::PASSWORD_AUTHENTICATED => true,
            AdminPasskeyGate::PASSKEY_VERIFIED => true,
        ])->get(route('admin.stores.index', $project->project_key))->assertOk();
    }

    public function test_new_staff_without_explicit_branch_assignment_has_no_branch_access(): void
    {
        $project = Project::factory()->create();
        $owner = AdminUser::factory()->create(['role' => 'project_admin', 'project_id' => $project->id]);
        $this->actingAs($owner, 'admin')->post(route('admin.settings.staff.store', $project->project_key), $this->staffData())->assertRedirect();
        $staff = AdminUser::where('login_id', 'branch-staff')->firstOrFail();
        $this->assertSame(0, $staff->stores()->count());
    }

    public function test_owner_of_all_projects_updates_shared_staff_without_changing_other_project_branches(): void
    {
        $a = Project::factory()->create();
        $b = Project::factory()->create();
        $owner = AdminUser::factory()->create(['role' => 'project_admin', 'project_id' => $a->id]);
        $owner->projects()->sync([$a->id, $b->id]);
        $staff = AdminUser::factory()->create(['role' => 'staff', 'project_id' => $b->id]);
        $staff->projects()->sync([$a->id, $b->id]);
        $branchA = $this->branch($a);
        $branchB = $this->branch($b);
        $staff->stores()->sync([$branchB->id => ['project_id' => $b->id]]);
        $this->actingAs($owner, 'admin')->put(route('admin.settings.staff.update', [$a->project_key, $staff->id]), $this->staffData(['email' => $staff->email, 'store_ids' => [$branchA->id]]))->assertRedirect();
        $this->assertEqualsCanonicalizing([$branchA->id, $branchB->id], $staff->stores()->pluck('stores.id')->all());
        $this->assertDatabaseHas('admin_user_stores', ['admin_user_id' => $staff->id, 'store_id' => $branchA->id, 'project_id' => $a->id]);
        $this->put(route('admin.settings.staff.update', [$a->project_key, $staff->id]), $this->staffData(['email' => $staff->email]))->assertRedirect();
        $this->assertSame([$branchB->id], $staff->stores()->pluck('stores.id')->all());
    }

    public function test_shared_account_cannot_be_modified_from_one_project(): void
    {
        $a = Project::factory()->create();
        $b = Project::factory()->create();
        $owner = AdminUser::factory()->create(['role' => AdminUser::ROLE_PROJECT_ADMIN, 'project_id' => $a->id]);
        $staff = AdminUser::factory()->create(['role' => AdminUser::ROLE_STAFF, 'project_id' => $a->id]);
        $staff->projects()->sync([$a->id, $b->id]);
        $this->actingAs($owner, 'admin')->put(route('admin.settings.staff.update', [$a->project_key, $staff->id]), $this->staffData())->assertForbidden();
    }

    public function test_null_branch_assignment_revokes_access_without_server_error(): void
    {
        $project = Project::factory()->create();
        $owner = AdminUser::factory()->create(['role' => 'project_admin', 'project_id' => $project->id]);
        $staff = AdminUser::factory()->create(['role' => 'staff', 'project_id' => $project->id]);
        $branch = $this->branch($project);
        $staff->stores()->sync([$branch->id => ['project_id' => $project->id]]);
        $data = $this->staffData(['email' => $staff->email, 'store_ids' => null]);
        unset($data['store_assignment']);

        $this->actingAs($owner, 'admin')->put(route('admin.settings.staff.update', [$project->project_key, $staff->id]), $data)
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(0, $staff->stores()->count());
    }
}
