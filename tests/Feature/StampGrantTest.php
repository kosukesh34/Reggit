<?php

namespace Tests\Feature;

use App\Enums\StampGrantLimit;
use App\Models\Member;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\StampGrantCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StampGrantTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U1234567890abcdef1234567890abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

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

    public function test_先着1名のqrは別会員でも再利用できないこと(): void
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
            ->assertJsonPath('error', '先着人数に達したため、このQRは終了しました');

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

    public function test_短縮トークンのqrからスタンプを付与できること(): void
    {
        [$project, $member, $card] = $this->createGrantFixtures(self::LINE_USER_ID);
        $code = $this->createGrantCode($project, $card, [
            'amount' => 3,
            'usage_limit' => StampGrantLimit::PER_MEMBER,
        ]);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->postJson('/api/stamps/grant-code/'.$code->token)
            ->assertOk()
            ->assertJsonPath('current_stamps', 3)
            ->assertJsonPath('granted_count', 3);

        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'stamp_card_id' => $card->id,
            'current_stamps' => 3,
        ]);
        $this->assertDatabaseHas('stamp_grant_redemptions', [
            'stamp_grant_code_id' => $code->id,
            'member_id' => $member->id,
        ]);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->postJson('/api/stamps/grant-code/'.$code->token)
            ->assertStatus(409)
            ->assertJsonPath('error', 'このQRからのスタンプは付与済みです');
    }

    public function test_短縮トークンの先着1名qrは別会員でも再利用できないこと(): void
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
        $code = $this->createGrantCode($project, $card, [
            'usage_limit' => StampGrantLimit::ONCE,
        ]);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->postJson('/api/stamps/grant-code/'.$code->token)
            ->assertOk();
        $this->withHeaders(['X-Line-User-Id' => $otherLineUserId])
            ->postJson('/api/stamps/grant-code/'.$code->token)
            ->assertStatus(409)
            ->assertJsonPath('error', '先着人数に達したため、このQRは終了しました');

        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'current_stamps' => 1,
        ]);
    }

    public function test_短縮トークンの無制限qrは同じ会員でも繰り返し付与できること(): void
    {
        [$project, $member, $card] = $this->createGrantFixtures(self::LINE_USER_ID);
        $code = $this->createGrantCode($project, $card, [
            'usage_limit' => StampGrantLimit::UNLIMITED,
            'expires_at' => null,
        ]);

        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];
        $this->withHeaders($headers)->postJson('/api/stamps/grant-code/'.$code->token)
            ->assertOk()
            ->assertJsonPath('current_stamps', 1);
        $this->withHeaders($headers)->postJson('/api/stamps/grant-code/'.$code->token)
            ->assertOk()
            ->assertJsonPath('current_stamps', 2);

        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'current_stamps' => 2,
        ]);
        $this->assertDatabaseCount('stamp_grant_redemptions', 0);
    }

    public function test_先着2名のqrは3人目と再利用した会員を拒否すること(): void
    {
        [$project, $member, $card] = $this->createGrantFixtures(self::LINE_USER_ID);
        $secondLineUserId = 'Uabcdef1234567890abcdef1234567890';
        $thirdLineUserId = 'Ufedcba0987654321fedcba0987654321';
        $secondUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $secondLineUserId,
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $secondUser->id,
        ]);
        $thirdUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $thirdLineUserId,
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $thirdUser->id,
        ]);
        $code = $this->createGrantCode($project, $card, [
            'usage_limit' => StampGrantLimit::ONCE,
            'usage_quota' => 2,
        ]);
        $path = '/api/stamps/grant-code/'.$code->token;

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])->postJson($path)
            ->assertOk()
            ->assertJsonPath('current_stamps', 1);
        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])->postJson($path)
            ->assertStatus(409)
            ->assertJsonPath('error', 'このQRからのスタンプは付与済みです');
        $this->withHeaders(['X-Line-User-Id' => $secondLineUserId])->postJson($path)
            ->assertOk()
            ->assertJsonPath('current_stamps', 1);
        $this->withHeaders(['X-Line-User-Id' => $thirdLineUserId])->postJson($path)
            ->assertStatus(409)
            ->assertJsonPath('error', '先着人数に達したため、このQRは終了しました');

        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'current_stamps' => 1,
        ]);
        $this->assertDatabaseCount('stamp_grant_redemptions', 2);
    }

    public function test_署名付きの先着2名qrは3人目を拒否すること(): void
    {
        [$project, $member, $card] = $this->createGrantFixtures(self::LINE_USER_ID);
        $secondLineUserId = 'Uabcdef1234567890abcdef1234567890';
        $thirdLineUserId = 'Ufedcba0987654321fedcba0987654321';
        $secondUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $secondLineUserId,
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $secondUser->id,
        ]);
        $thirdUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $thirdLineUserId,
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $thirdUser->id,
        ]);
        $grantUrl = $this->signedGrantUrl($project, $card, 'quota-nonce', 1, StampGrantLimit::ONCE, 2);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])->postJson($grantUrl)
            ->assertOk();
        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])->postJson($grantUrl)
            ->assertStatus(409)
            ->assertJsonPath('error', 'このQRからのスタンプは付与済みです');
        $this->withHeaders(['X-Line-User-Id' => $secondLineUserId])->postJson($grantUrl)
            ->assertOk();
        $this->withHeaders(['X-Line-User-Id' => $thirdLineUserId])->postJson($grantUrl)
            ->assertStatus(409)
            ->assertJsonPath('error', '先着人数に達したため、このQRは終了しました');

        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'current_stamps' => 1,
        ]);
    }

    public function test_1日1回のqrは同じ会員は当日のみ再利用できず翌日と別会員は付与できること(): void
    {
        $this->travelTo(Carbon::parse('2026-08-14 10:00:00'));
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
        $code = $this->createGrantCode($project, $card, [
            'usage_limit' => StampGrantLimit::DAILY,
            'expires_at' => now()->addDays(2),
        ]);
        $path = '/api/stamps/grant-code/'.$code->token;
        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];

        $this->withHeaders($headers)->postJson($path)
            ->assertOk()
            ->assertJsonPath('current_stamps', 1);
        $this->withHeaders($headers)->postJson($path)
            ->assertStatus(409)
            ->assertJsonPath('error', '本日はこのQRからのスタンプを付与済みです');
        $this->withHeaders(['X-Line-User-Id' => $otherLineUserId])->postJson($path)
            ->assertOk()
            ->assertJsonPath('current_stamps', 1);

        $this->travelTo(Carbon::parse('2026-08-15 00:01:00'));
        $this->withHeaders($headers)->postJson($path)
            ->assertOk()
            ->assertJsonPath('current_stamps', 2);

        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'current_stamps' => 2,
        ]);
        $this->assertDatabaseCount('stamp_grant_redemptions', 3);
    }

    public function test_署名付きの1日1回qrは同じ会員は当日のみ再利用できず翌日は付与できること(): void
    {
        $this->travelTo(Carbon::parse('2026-08-14 10:00:00'));
        [$project, $member, $card] = $this->createGrantFixtures(self::LINE_USER_ID);
        $grantUrl = URL::temporarySignedRoute('api.stamps.grant', now()->addDays(2), [
            'stampCard' => $card->id,
            'nonce' => 'daily-nonce',
            'project_key' => $project->project_key,
            'amount' => 1,
            'limit' => StampGrantLimit::DAILY,
        ]);
        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];

        $this->withHeaders($headers)->postJson($grantUrl)
            ->assertOk()
            ->assertJsonPath('current_stamps', 1);
        $this->withHeaders($headers)->postJson($grantUrl)
            ->assertStatus(409)
            ->assertJsonPath('error', '本日はこのQRからのスタンプを付与済みです');

        $this->travelTo(Carbon::parse('2026-08-15 00:01:00'));
        $this->withHeaders($headers)->postJson($grantUrl)
            ->assertOk()
            ->assertJsonPath('current_stamps', 2);

        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'current_stamps' => 2,
        ]);
    }

    public function test_お客様1人1回のqrは翌日も同じ会員は再利用できないこと(): void
    {
        $this->travelTo(Carbon::parse('2026-08-14 10:00:00'));
        [$project, $member, $card] = $this->createGrantFixtures(self::LINE_USER_ID);
        $code = $this->createGrantCode($project, $card, [
            'usage_limit' => StampGrantLimit::PER_MEMBER,
            'expires_at' => now()->addDays(2),
        ]);
        $path = '/api/stamps/grant-code/'.$code->token;
        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];

        $this->withHeaders($headers)->postJson($path)->assertOk();
        $this->travelTo(Carbon::parse('2026-08-15 00:01:00'));
        $this->withHeaders($headers)->postJson($path)
            ->assertStatus(409)
            ->assertJsonPath('error', 'このQRからのスタンプは付与済みです');

        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'current_stamps' => 1,
        ]);
        $this->assertDatabaseCount('stamp_grant_redemptions', 1);
    }

    public function test_期限切れの短縮トークンは付与できないこと(): void
    {
        [$project, $member, $card] = $this->createGrantFixtures(self::LINE_USER_ID);
        $code = $this->createGrantCode($project, $card, [
            'expires_at' => now()->subMinute(),
        ]);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->postJson('/api/stamps/grant-code/'.$code->token)
            ->assertStatus(410)
            ->assertJsonPath('error', 'このQRの有効期限が切れています');

        $this->assertDatabaseMissing('member_stamps', [
            'member_id' => $member->id,
        ]);
    }

    public function test_短縮トークンの公開urlはスタンプ画面へリダイレクトすること(): void
    {
        [$project, $member, $card] = $this->createGrantFixtures(self::LINE_USER_ID);
        $code = $this->createGrantCode($project, $card);

        $location = $this->get('/g/'.$code->token)->assertRedirect()->headers->get('Location');
        $this->assertNotNull($location);
        $query = parse_url($location, PHP_URL_QUERY) ?? '';
        parse_str($query, $params);
        $liffState = $params['liff.state'] ?? $params['liff_state'] ?? null;
        if (is_string($liffState) && $liffState !== '') {
            parse_str($liffState, $params);
        }
        $this->assertSame('stamps', $params['tab'] ?? null);
        $this->assertSame($code->token, $params['g'] ?? null);
        $this->assertSame('/g/'.$code->token, parse_url($code->openUrl(), PHP_URL_PATH));

        $pathPattern = '/\/g\/([0-9a-f]{12})(?:[\/?#]|$)/i';
        foreach ([$code->openUrl(), $code->openUrl().'?x=1', '/g/'.$code->token] as $raw) {
            $this->assertSame(1, preg_match($pathPattern, $raw, $matches), $raw);
            $this->assertSame($code->token, $matches[1]);
        }
    }

    public function test_存在しない短縮トークンの公開urlは404になること(): void
    {
        $this->get('/g/ffffffffffff')->assertNotFound();
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

    /**
     * @param  array{
     *     amount?: int,
     *     usage_limit?: string,
     *     usage_quota?: int,
     *     expires_at?: Carbon|null,
     *     token?: string,
     * }  $attrs
     */
    private function createGrantCode(Project $project, StampCard $card, array $attrs = []): StampGrantCode
    {
        return StampGrantCode::query()->create(array_merge([
            'project_id' => $project->id,
            'stamp_card_id' => $card->id,
            'token' => bin2hex(random_bytes(6)),
            'amount' => 1,
            'usage_limit' => StampGrantLimit::PER_MEMBER,
            'usage_quota' => 1,
            'expires_at' => now()->addMinutes(15),
        ], $attrs));
    }

    private function signedGrantUrl(
        Project $project,
        StampCard $card,
        string $nonce,
        int $amount,
        string $limit,
        int $quota = 1,
    ): string {
        return URL::temporarySignedRoute('api.stamps.grant', now()->addMinutes(15), [
            'stampCard' => $card->id,
            'nonce' => $nonce,
            'project_key' => $project->project_key,
            'amount' => $amount,
            'limit' => $limit,
            'quota' => $quota,
        ]);
    }
}
