<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\AdminUser;
use App\Models\Project;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReservationsTest extends TestCase
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

    public function test_reservations_予約一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.reservations', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_reservation_status_予約ステータスを更新できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $reservation = Reservation::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'status' => ReservationStatus::PENDING,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.reservations.detail', ['project_key' => $project->project_key, 'id' => $reservation->id]))
            ->post(route('admin.reservations.status', [
                'project_key' => $project->project_key,
                'id' => $reservation->id,
            ]), ['status' => ReservationStatus::CONFIRMED]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'project_id' => $project->id,
            'status' => ReservationStatus::CONFIRMED,
        ]);
    }

    public function test_reservation_complete_予約を完了できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $reservation = Reservation::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'status' => ReservationStatus::CONFIRMED,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.reservations.complete', [
                'project_key' => $project->project_key,
                'id' => $reservation->id,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success', '予約を完了しました');
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'project_id' => $project->id,
            'status' => ReservationStatus::COMPLETED,
        ]);
    }
}
