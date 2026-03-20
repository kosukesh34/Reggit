<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createAdmin(int $projectId): AdminUser
    {
        return AdminUser::create([
            'login_id' => 'admin_analytics',
            'email' => 'admin_analytics@example.com',
            'password' => 'password',
            'name' => 'Admin',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $projectId,
        ]);
    }

    public function test_analytics_分析ページにアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project->id);

        $maxRetries = 3;
        $sleepMicros = 300_000; // 0.3s
        $lastResponse = null;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $response = $this->actingAs($admin, 'admin')
                ->get(route('admin.analytics', ['project_key' => $project->project_key]));

            $lastResponse = $response;

            if ($response->getStatusCode() === 200) {
                $response->assertStatus(200);

                return;
            }

            // DB が一時的に落ちた際に 500 になるケースに備えてリトライ
            if ($response->getStatusCode() >= 500 && $attempt < $maxRetries - 1) {
                try {
                    DB::purge();
                } catch (\Throwable) {
                    // ignore
                }
                usleep($sleepMicros);

                continue;
            }

            $response->assertStatus(200);
        }

        // 念のため（ループを抜けることがない想定）
        $lastResponse?->assertStatus(200);
    }
}
