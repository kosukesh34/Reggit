<?php

namespace Tests\Feature;

use App\Enums\StampCycleMode;
use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\MemberStampTransaction;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\User;
use App\Services\Admin\MemberAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StampCycleTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U1234567890abcdef1234567890abcdef';

    public function test_1周で終了するカードは必要数で止まり追加できないこと(): void
    {
        [$member, $card] = $this->createCard(StampCycleMode::ONCE, 5);
        $service = resolve(MemberAdminService::class);
        $service->addStampsToMember($member, (int) $card->id, 7);

        $stamp = MemberStamp::query()->where('member_id', $member->id)->where('stamp_card_id', $card->id)->first();
        $this->assertNotNull($stamp);
        $this->assertSame(5, (int) $stamp->current_stamps);
        $this->assertNotNull($stamp->completed_at);

        $service->addStampsToMember($member, (int) $card->id, 2);
        $stamp->refresh();
        $this->assertSame(5, (int) $stamp->current_stamps);
    }

    public function test_繰り返すカードは達成後に枠がリセットされること(): void
    {
        [$member, $card] = $this->createCard(StampCycleMode::REPEAT, 5);
        $service = resolve(MemberAdminService::class);
        $result = $service->addStampsToMember($member, (int) $card->id, 7);

        $this->assertSame(2, (int) $result->current_stamps);
        $this->assertSame(1, (int) $result->cycle_count);
        $this->assertNull($result->completed_at);
        $this->assertSame(1, $result->cyclesCompletedNow);
        $this->assertSame(7, $member->fresh()->getTotalStamps());
        $this->assertSame(1, MemberStampTransaction::query()->where('member_id', $member->id)->count());
        $this->assertDatabaseHas('member_stamp_transactions', [
            'member_id' => $member->id,
            'delta' => 7,
        ]);
    }

    public function test_繰り返すカードの減算は周回を巻き戻すこと(): void
    {
        [$member, $card] = $this->createCard(StampCycleMode::REPEAT, 5);
        $service = resolve(MemberAdminService::class);
        $service->addStampsToMember($member, (int) $card->id, 7);
        $service->adjustStampsToMember($member, (int) $card->id, -3, '減算');

        $stamp = MemberStamp::query()->where('member_id', $member->id)->where('stamp_card_id', $card->id)->first();
        $this->assertNotNull($stamp);
        $this->assertSame(4, (int) $stamp->current_stamps);
        $this->assertSame(0, (int) $stamp->cycle_count);
    }

    public function test_次カード切替では達成後に余りが次のカードへ移ること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::LINE_USER_ID]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $next = StampCard::factory()->create([
            'project_id' => $project->id,
            'name' => 'シルバー',
            'required_stamps' => 10,
            'is_active' => true,
            'cycle_mode' => StampCycleMode::ONCE,
        ]);
        $current = StampCard::factory()->create([
            'project_id' => $project->id,
            'name' => 'ブロンズ',
            'required_stamps' => 5,
            'is_active' => true,
            'cycle_mode' => StampCycleMode::NEXT_CARD,
            'next_stamp_card_id' => $next->id,
        ]);

        resolve(MemberAdminService::class)->addStampsToMember($member, (int) $current->id, 7);

        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'stamp_card_id' => $current->id,
            'current_stamps' => 5,
        ]);
        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'stamp_card_id' => $next->id,
            'current_stamps' => 2,
        ]);
        $this->assertNotNull(
            MemberStamp::query()->where('stamp_card_id', $current->id)->value('completed_at')
        );
    }

    public function test_次カードは前のカード達成まで一覧に出ないこと(): void
    {
        $project = Project::factory()->create(['project_key' => 'cycle-shop']);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => self::LINE_USER_ID,
        ]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $next = StampCard::factory()->create([
            'project_id' => $project->id,
            'name' => 'シルバー',
            'required_stamps' => 10,
            'is_active' => true,
            'cycle_mode' => StampCycleMode::ONCE,
        ]);
        $current = StampCard::factory()->create([
            'project_id' => $project->id,
            'name' => 'ブロンズ',
            'required_stamps' => 5,
            'is_active' => true,
            'cycle_mode' => StampCycleMode::NEXT_CARD,
            'next_stamp_card_id' => $next->id,
        ]);

        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];
        $this->withHeaders($headers)
            ->get('/api/stamps?project_key='.$project->project_key)
            ->assertOk()
            ->assertJsonCount(1, 'stamp_cards')
            ->assertJsonPath('stamp_cards.0.name', 'ブロンズ')
            ->assertJsonPath('stamp_cards.0.is_archived', false);

        resolve(MemberAdminService::class)->addStampsToMember($member, (int) $current->id, 5);

        $this->withHeaders($headers)
            ->get('/api/stamps?project_key='.$project->project_key)
            ->assertOk()
            ->assertJsonCount(2, 'stamp_cards')
            ->assertJsonPath('stamp_cards.0.name', 'シルバー')
            ->assertJsonPath('stamp_cards.0.is_archived', false)
            ->assertJsonPath('stamp_cards.1.name', 'ブロンズ')
            ->assertJsonPath('stamp_cards.1.is_archived', true);
    }

    /**
     * @return array{0: Member, 1: StampCard}
     */
    private function createCard(string $cycleMode, int $required): array
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'required_stamps' => $required,
            'is_active' => true,
            'cycle_mode' => $cycleMode,
        ]);

        return [$member, $card];
    }
}
