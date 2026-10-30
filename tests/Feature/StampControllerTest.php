<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\MemberStampTransaction;
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

    public function test_history_自分のカードの履歴だけを決まった言葉で新しい順に返すこと(): void
    {
        $this->createProject();
        // 会員を特定するには LINE の形式の ID が要る
        $lineUserId = 'U'.str_repeat('a1', 16);
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $other = Member::factory()->create(['project_id' => 1]);
        $card = StampCard::factory()->create(['project_id' => 1]);
        $otherCard = StampCard::factory()->create(['project_id' => 1]);
        $row = fn (Member $owner, StampCard $stampCard, int $delta, int $balance, ?string $description) => MemberStampTransaction::query()->create(['project_id' => 1, 'member_id' => $owner->id, 'stamp_card_id' => $stampCard->id, 'delta' => $delta, 'balance_after' => $balance, 'description' => $description]);
        $row($member, $card, 1, 1, '店舗QRから付与');
        $row($member, $card, -1, 0, 'クレーム対応のため取消');
        $row($member, $card, 2, 2, '管理メモ');
        $row($member, $otherCard, 5, 5, null);
        $row($other, $card, 3, 3, null);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->get("/api/stamps/{$card->id}/history?project_key=default")
            ->assertOk();

        $this->assertSame([[2, 'スタンプ獲得'], [-1, 'スタンプの取り消し'], [1, 'スタンプ獲得']], array_map(fn ($item) => [$item['delta'], $item['label']], $response->json('history')));
        $response->assertDontSee('クレーム対応')->assertDontSee('管理メモ');
    }
}
