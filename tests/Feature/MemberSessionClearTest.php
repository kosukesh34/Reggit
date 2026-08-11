<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class MemberSessionClearTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000003';

    public function test_session_clear_ユーザーセッションをクリアできること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        Session::put('mobile_order_user_id_'.$project->id, $user->id);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/session/clear?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertNull(Session::get('mobile_order_user_id_'.$project->id));
        $this->assertFalse($user->fresh()->trashed());
    }
}
