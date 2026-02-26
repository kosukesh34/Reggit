<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampControllerTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'line-stamp-1';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_index_スタンプカード一覧を返すこと(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $card = StampCard::factory()->create(['project_id' => 1]);
        MemberStamp::factory()->create([
            'project_id' => 1,
            'member_id' => $member->id,
            'stamp_card_id' => $card->id,
            'current_stamps' => 3,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/stamps?project_key=default');

        $response->assertStatus(200)
            ->assertJsonStructure(['stamp_cards', 'default_image_url']);
    }
}
