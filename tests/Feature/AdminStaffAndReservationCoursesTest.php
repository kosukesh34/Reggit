<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\ReservationCourse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffAndReservationCoursesTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_staff_with_permissions_but_staff_cannot_manage_staff(): void
    {
        $project = Project::factory()->create();
        $manager = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);

        $this->actingAs($manager, 'admin')->post(route('admin.settings.staff.store', ['project_key' => $project->project_key]), [
            'name' => '厨房担当',
            'login_id' => 'kitchen-staff',
            'email' => 'kitchen-staff@example.test',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'permissions' => ['orders.view', 'orders.update'],
        ])->assertRedirect();

        $staff = AdminUser::where('login_id', 'kitchen-staff')->firstOrFail();
        $this->assertSame(AdminUser::ROLE_STAFF, $staff->role);
        $this->assertSame($project->id, $staff->project_id);
        $this->assertTrue($staff->hasPermission('orders.update'));
        $this->assertFalse($staff->hasPermission('settings.manage'));

        $this->actingAs($staff, 'admin')
            ->get(route('admin.settings.staff', ['project_key' => $project->project_key]))
            ->assertForbidden();
    }

    public function test_reservation_course_is_managed_in_reservation_settings(): void
    {
        $project = Project::factory()->create();
        $manager = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);

        $this->actingAs($manager, 'admin')
            ->post(route('admin.settings.reservations.courses.store', ['project_key' => $project->project_key]), [
                'name' => 'ランチコース',
                'price' => 2500,
                'description' => '前菜とメイン',
            ])->assertRedirect();

        $course = ReservationCourse::where('project_id', $project->id)->firstOrFail();
        $this->assertSame(2500, $course->price);
        $this->actingAs($manager, 'admin')
            ->get(route('admin.settings.reservations', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee('ランチコース');
    }
}
