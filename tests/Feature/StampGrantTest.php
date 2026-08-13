<?php

namespace Tests\Feature;

use App\Enums\StampGrantLimit;
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
            ->assertJsonPath('current_stamps', 1)
            ->assertJsonPath('granted_count', 1);

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

    public function test_署名付きqrから指定個数のスタンプを付与できること(): void
    {
        [$project, $member, $card] = $this->createGrantFixtures(self::LINE_USER_ID);
        $grantUrl = $this->signedGrantUrl($project, $card, 'qty-nonce', 3, StampGrantLimit::PER_MEMBER);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])->postJson($grantUrl)
            ->assertOk()
            ->assertJsonPath('current_stamps', 3)
            ->assertJsonPath('granted_count', 3)
            ->assertJsonPath('message', 'スタンプを3個追加しました');

        $this->assertDatabaseHas('member_stamps', [
            'project_id' => $project->id,
            'member_id' => $member->id,
            'stamp_card_id' => $card->id,
            'current_stamps' => 3,
        ]);
    }

    public function test_全体1回のqrは別会員でも再利用できないこと(): void
    {
        [$project, $member, $card] = $this->createGrantFixtures(self::LINE_USER_ID);
        $otherLineUserId = 'Uabcdef1234567890abcdef1234567890';
        $otherUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $otherLineUserId,
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $otherUser->id,
        ]);
        $grantUrl = $this->signedGrantUrl($project, $card, 'once-nonce', 1, StampGrantLimit::ONCE);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])->postJson($grantUrl)
            ->assertOk()
            ->assertJsonPath('current_stamps', 1);

        $this->withHeaders(['X-Line-User-Id' => $otherLineUserId])->postJson($grantUrl)
            ->assertStatus(409)
            ->assertJsonPath('error', 'このQRは使用済みです');

        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'current_stamps' => 1,
        ]);
    }

    public function test_無制限のqrは同じ会員でも繰り返し付与できること(): void
    {
        [$project, $member, $card] = $this->createGrantFixtures(self::LINE_USER_ID);
        $grantUrl = $this->signedGrantUrl($project, $card, 'unlimited-nonce', 1, StampGrantLimit::UNLIMITED);

        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];
        $this->withHeaders($headers)->postJson($grantUrl)
            ->assertOk()
            ->assertJsonPath('current_stamps', 1);
        $this->withHeaders($headers)->postJson($grantUrl)
            ->assertOk()
            ->assertJsonPath('current_stamps', 2);

        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'stamp_card_id' => $card->id,
            'current_stamps' => 2,
        ]);
    }

    /** @return array{0: Project, 1: Member, 2: StampCard} */
    private function createGrantFixtures(string $lineUserId): array
    {
        $project = Project::factory()->create(['project_key' => 'stamp-shop']);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        return [$project, $member, $card];
    }

    private function signedGrantUrl(
        Project $project,
        StampCard $card,
        string $nonce,
        int $amount,
        string $limit,
    ): string {
        return URL::temporarySignedRoute('api.stamps.grant', now()->addMinutes(15), [
            'stampCard' => $card->id,
            'nonce' => $nonce,
            'project_key' => $project->project_key,
            'amount' => $amount,
            'limit' => $limit,
        ]);
    }
}
