<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StampGrantTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U1234567890abcdef1234567890abcdef';

    public function test_署名付きqrからスタンプを一度だけ付与できること(): void
    {
        $project = Project::factory()->create(['project_key' => 'stamp-shop']);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => self::LINE_USER_ID,
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        $grantUrl = URL::temporarySignedRoute('api.stamps.grant', now()->addMinutes(15), [
            'stampCard' => $card->id,
            'nonce' => 'test-stamp-nonce',
            'project_key' => $project->project_key,
        ]);

        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];
        $this->withHeaders($headers)->postJson($grantUrl)
            ->assertOk()
            ->assertJsonPath('current_stamps', 1);

        $this->assertDatabaseHas('member_stamps', [
            'project_id' => $project->id,
            'member_id' => $member->id,
            'stamp_card_id' => $card->id,
            'current_stamps' => 1,
        ]);

        $this->withHeaders($headers)->postJson($grantUrl)
            ->assertStatus(409)
            ->assertJsonPath('error', 'このQRからのスタンプは付与済みです');
    }

    public function test_署名のない付与urlは拒否すること(): void
    {
        $project = Project::factory()->create(['project_key' => 'stamp-shop']);
        $card = StampCard::factory()->create(['project_id' => $project->id, 'is_active' => true]);

        $this->postJson('/api/stamps/grant/'.$card->id.'?project_key='.$project->project_key.'&nonce=unsigned')
            ->assertForbidden();
    }
}
