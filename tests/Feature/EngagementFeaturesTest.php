<?php

namespace Tests\Feature;

use App\Domain\Reservation\Repositories\ReservationReminderRepository;
use App\Enums\ReservationStatus;
use App\Http\Requests\Admin\SurveyRequest;
use App\Infrastructure\Persistence\Eloquent\Settings\LineSettingStore;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AcquisitionSource;
use App\Models\AdminUser;
use App\Models\Announcement;
use App\Models\Member;
use App\Models\MemberTag;
use App\Models\Project;
use App\Models\PromoPopup;
use App\Models\Reservation;
use App\Models\Store;
use App\Models\Survey;
use App\Models\User;
use App\Services\AnnouncementNotificationService;
use App\Services\Line\LineService;
use App\Services\PushNotificationService;
use App\UseCases\Reservation\SendReservationRemindersUseCase;
use App\UseCases\Survey\SendSurveyInvitationsUseCase;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/** アンケート・予約リマインド・流入経路・宣伝ポップアップ */
class EngagementFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000002';

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    /** @return array{Project, User, Member} */
    private function member(): array
    {
        $project = Project::firstOrCreate(['project_key' => 'default'], ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::LINE_USER_ID]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'status' => 'active', 'points' => 0]);

        return [$project, $user, $member];
    }

    private function survey(Project $project, array $attributes = []): Survey
    {
        return Survey::create([
            'project_id' => $project->id,
            'title' => 'ご来店アンケート',
            'questions' => [
                ['type' => 'nps', 'label' => 'おすすめ度', 'required' => true],
                ['type' => 'choice', 'label' => 'ご利用目的', 'options' => ['ランチ', 'ディナー'], 'required' => false],
                ['type' => 'text', 'label' => 'ご意見', 'required' => false],
            ],
            'reward_points' => 30,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    private function fakeNotifier(int $times): void
    {
        $notifier = Mockery::mock(AnnouncementNotificationService::class);
        $notifier->shouldReceive('send')->times($times);
        $this->app->instance(AnnouncementNotificationService::class, $notifier);
    }

    public function test_アンケートに回答するとポイントとnpsタグが付き二重回答はできないこと(): void
    {
        [$project, , $member] = $this->member();
        $survey = $this->survey($project);
        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];
        $url = "/api/surveys/{$survey->id}/answers?project_key=default";

        $this->withHeaders($headers)->postJson($url, ['answers' => [null, 'ランチ', '']])
            ->assertStatus(422)->assertJsonValidationErrors(['answers.0']);
        $this->withHeaders($headers)->postJson($url, ['answers' => [9, '朝食', '']])
            ->assertStatus(422)->assertJsonValidationErrors(['answers.1']);

        $this->withHeaders($headers)->postJson($url, ['answers' => [9, 'ランチ', 'おいしかった']])
            ->assertOk()->assertJsonPath('data.granted_points', 30);
        $this->withHeaders($headers)->postJson($url, ['answers' => [3, 'ランチ', '']])->assertStatus(422);

        $this->assertSame(30, $member->fresh()->points);
        $this->assertSame(['NPS推奨者'], $member->tags()->pluck('name')->all());
        $this->assertDatabaseHas('survey_responses', ['survey_id' => $survey->id, 'member_id' => $member->id, 'nps_score' => 9]);
        $this->withHeaders($headers)->getJson("/api/surveys/{$survey->id}?project_key=default")->assertJsonPath('survey.answered', true);
    }

    public function test_受付期間外のアンケートには回答できないこと(): void
    {
        [$project] = $this->member();
        $survey = $this->survey($project, ['ends_at' => now()->subMinute()]);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->postJson("/api/surveys/{$survey->id}/answers?project_key=default", ['answers' => [10, null, null]])
            ->assertStatus(410);
        $this->getJson('/api/surveys?project_key=default')->assertOk()->assertJsonCount(0, 'surveys');
    }

    public function test_来店の翌日に一度だけ回答依頼を送ること(): void
    {
        [$project, , $member] = $this->member();
        $survey = $this->survey($project, ['invite_trigger' => 'visit', 'invite_delay_days' => 1]);
        DB::table('member_visits')->insert(['project_id' => $project->id, 'member_id' => $member->id, 'visited_at' => now()->subDay(), 'source' => 'manual', 'created_at' => now(), 'updated_at' => now()]);
        $this->fakeNotifier(1);

        $this->assertSame(1, app(SendSurveyInvitationsUseCase::class)->handle());
        $this->assertSame(0, app(SendSurveyInvitationsUseCase::class)->handle());

        $this->assertDatabaseHas('announcements', ['project_id' => $project->id, 'title' => $survey->title, 'action_path' => '/survey?survey='.$survey->id]);
    }

    public function test_遷移先付きのお知らせはlineのボタンからその画面を開くこと(): void
    {
        [$project, , $member] = $this->member();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendAnnouncement')->andReturn(['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'notification_ids' => []]);
        $this->app->instance(PushNotificationService::class, $push);
        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('pushMessage')->once()->with(self::LINE_USER_ID, Mockery::on(function (array $message): bool {
            $button = $message['contents']['footer']['contents'][0]['action'];

            return $button['label'] === 'アンケートに答える'
                && str_contains(urldecode(urldecode($button['uri'])), 'tab=survey&survey=5');
        }), 'test-token')->andReturn([]);
        $this->app->instance(LineService::class, $line);

        app(AnnouncementNotificationService::class)->send(Announcement::create([
            'project_id' => $project->id, 'title' => 'アンケート', 'body' => 'ご協力ください',
            'action_path' => '/survey?survey=5', 'action_label' => 'アンケートに答える',
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL, 'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'target_member_ids' => [(int) $member->id], 'published_at' => now(), 'sent_at' => now(),
        ]));
    }

    public function test_予約の指定時間前に一度だけリマインドを送ること(): void
    {
        [$project, $user, $member] = $this->member();
        ShopSettingStore::setValue('reservation_reminder_hours', 24, 'integer', $project->id);
        $due = Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'reserved_at' => now()->addHours(20), 'status' => ReservationStatus::CONFIRMED]);
        $due->forceFill(['created_at' => now()->subDays(3)])->save();
        // 予約から来店まで24時間ない予約と、まだ先の予約には送らない
        Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'reserved_at' => now()->addHours(5), 'status' => ReservationStatus::CONFIRMED]);
        Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'reserved_at' => now()->addDays(3), 'status' => ReservationStatus::CONFIRMED]);
        $this->fakeNotifier(1);

        $this->assertSame(1, app(SendReservationRemindersUseCase::class)->handle());
        $this->assertSame(0, app(SendReservationRemindersUseCase::class)->handle());

        $this->assertNotNull($due->fresh()->reminder_sent_at);
        $announcement = Announcement::withoutGlobalScopes()->where('title', 'ご予約のリマインド')->sole();
        $this->assertSame([(int) $member->id], $announcement->target_member_ids);
        $this->assertSame('/reservation', $announcement->action_path);
    }

    public function test_リマインドを設定していなければ送らないこと(): void
    {
        [$project, $user] = $this->member();
        $reservation = Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'reserved_at' => now()->addHours(2), 'status' => ReservationStatus::PENDING]);
        $reservation->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->fakeNotifier(0);

        $this->assertSame(0, app(SendReservationRemindersUseCase::class)->handle());
    }

    public function test_流入経路のqrから登録すると経路とタグを記録すること(): void
    {
        $project = Project::firstOrCreate(['project_key' => 'default'], ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]);
        config(['smaregi.contract_id' => '']);
        $source = AcquisitionSource::create(['project_id' => $project->id, 'name' => '店頭POP', 'code' => 'a1b2c3d4e5']);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/register?project_key=default', ['name' => 'Test User', 'phone' => '090-1234-5678', 'acquisition_source' => 'a1b2c3d4e5'])
            ->assertStatus(201);

        $member = Member::withoutGlobalScopes()->where('project_id', $project->id)->sole();
        $this->assertSame($source->id, $member->acquisition_source_id);
        $this->assertSame(['流入:店頭POP'], $member->tags()->pluck('name')->all());
    }

    public function test_表示期間中で有効なポップアップだけを返すこと(): void
    {
        $project = Project::firstOrCreate(['project_key' => 'default'], ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]);
        $base = ['project_id' => $project->id, 'display_type' => 'fullscreen', 'frequency' => 'once', 'is_active' => true];
        PromoPopup::create([...$base, 'title' => '表示中']);
        PromoPopup::create([...$base, 'title' => '停止中', 'is_active' => false]);
        PromoPopup::create([...$base, 'title' => '終了', 'ends_at' => now()->subMinute()]);
        PromoPopup::create([...$base, 'title' => '開始前', 'starts_at' => now()->addDay()]);

        $this->getJson('/api/promo-popups?project_key=default')->assertOk()
            ->assertJsonCount(1, 'popups')->assertJsonPath('popups.0.title', '表示中')->assertJsonPath('popups.0.display_type', 'fullscreen');
    }

    public function test_複数選択の回答で選択肢のタグを付けcsvで出力できること(): void
    {
        [$project, , $member] = $this->member();
        $member->update(['member_number' => 'M0001']);
        $survey = $this->survey($project, ['questions' => [
            ['type' => 'multi_choice', 'label' => '目的', 'options' => ['ランチ', 'ディナー'], 'option_tags' => ['ランチ' => 'ランチ利用'], 'required' => true],
            ['type' => 'rating', 'label' => '味', 'required' => true],
            ['type' => 'text', 'label' => 'ご意見', 'required' => false],
        ]]);
        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];

        $this->withHeaders($headers)->getJson("/api/surveys/{$survey->id}?project_key=default")->assertOk()
            ->assertJsonMissingPath('survey.questions.0.option_tags');
        $this->withHeaders($headers)->postJson("/api/surveys/{$survey->id}/answers?project_key=default", ['answers' => [['ディナー', 'ランチ'], 4, '=HYPERLINK("x")']])
            ->assertOk();

        $this->assertSame(['ランチ利用'], $member->tags()->pluck('name')->all());
        $this->assertSame([['ランチ', 'ディナー'], 4, '=HYPERLINK("x")'], $survey->responses()->sole()->answers);

        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $csv = $this->actingAs($admin, 'admin')->get(route('admin.surveys.export', ['project_key' => 'default', 'id' => $survey->id]))
            ->assertOk()->streamedContent();
        $this->assertStringStartsWith("\u{FEFF}回答日時,会員番号,目的,味,ご意見", $csv);
        $this->assertStringContainsString(',M0001,ランチ、ディナー,4,"\'=HYPERLINK(""x"")"', $csv);
    }

    public function test_注文と会員登録をきっかけに回答依頼を送ること(): void
    {
        [$project, $user, $member] = $this->member();
        $order = $this->survey($project, ['title' => '注文アンケート', 'invite_trigger' => 'order', 'invite_delay_days' => 1]);
        $registration = $this->survey($project, ['title' => '登録アンケート', 'invite_trigger' => 'registration', 'invite_delay_days' => 3]);
        DB::table('orders')->insert(['project_id' => $project->id, 'user_id' => $user->id, 'order_number' => 'T-1', 'status' => 'completed', 'total_amount' => 1000, 'created_at' => now()->subDay(), 'updated_at' => now()]);
        $member->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->fakeNotifier(2);

        $this->assertSame(2, app(SendSurveyInvitationsUseCase::class)->handle());

        $this->assertDatabaseHas('announcements', ['title' => '注文アンケート', 'action_path' => '/survey?survey='.$order->id]);
        $this->assertDatabaseHas('announcements', ['title' => '登録アンケート', 'action_path' => '/survey?survey='.$registration->id]);
    }

    public function test_表示相手と店舗でポップアップを絞ること(): void
    {
        [$project, , $member] = $this->member();
        $storeA = Store::create(['project_id' => $project->id, 'store_key' => 'a', 'name' => 'A店', 'is_active' => true]);
        $storeB = Store::create(['project_id' => $project->id, 'store_key' => 'b', 'name' => 'B店', 'is_active' => true]);
        $tag = MemberTag::create(['project_id' => $project->id, 'name' => 'VIP', 'color' => '#123456']);
        $member->tags()->attach($tag->id);
        $member->update(['rank' => 'silver']);
        $base = ['project_id' => $project->id, 'display_type' => 'modal', 'frequency' => 'once', 'is_active' => true];
        PromoPopup::create([...$base, 'title' => '未会員向け', 'audience' => 'guests']);
        PromoPopup::create([...$base, 'title' => '会員向け', 'audience' => 'members']);
        PromoPopup::create([...$base, 'title' => 'VIP向け', 'audience' => 'segment', 'audience_tag_ids' => [$tag->id]]);
        PromoPopup::create([...$base, 'title' => 'ゴールド向け', 'audience' => 'segment', 'audience_ranks' => ['gold']]);
        PromoPopup::create([...$base, 'title' => 'B店だけ', 'audience' => 'all', 'store_ids' => [$storeB->id]]);
        $titles = fn ($response) => collect($response->assertOk()->json('popups'))->pluck('title')->sort()->values()->all();

        $this->assertSame(['未会員向け'], $titles($this->getJson('/api/promo-popups?project_key=default')));
        $member_headers = ['X-Line-User-Id' => self::LINE_USER_ID];
        $this->assertSame(['VIP向け', '会員向け'], $titles($this->withHeaders([...$member_headers, 'X-Store-Id' => (string) $storeA->id])->getJson('/api/promo-popups?project_key=default')));
        $this->assertSame(['B店だけ', 'VIP向け', '会員向け'], $titles($this->withHeaders([...$member_headers, 'X-Store-Id' => (string) $storeB->id])->getJson('/api/promo-popups?project_key=default')));
    }

    public function test_通知に失敗してもお知らせは1件だけ作り再送の仕組みで送り直し止まった日の分も拾うこと(): void
    {
        [$project, $user, $member] = $this->member();
        $survey = $this->survey($project, ['invite_trigger' => 'visit', 'invite_delay_days' => 1]);
        $survey->forceFill(['created_at' => now()->subDays(10)])->save();
        // 3日前の来店（1日後に送るはずが、スケジューラーが止まっていた想定）
        DB::table('member_visits')->insert(['project_id' => $project->id, 'member_id' => $member->id, 'visited_at' => now()->subDays(3), 'source' => 'manual', 'created_at' => now(), 'updated_at' => now()]);
        ShopSettingStore::setValue('reservation_reminder_hours', 24, 'integer', $project->id);
        $reservation = Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'reserved_at' => now()->addHours(20), 'status' => ReservationStatus::CONFIRMED]);
        $reservation->forceFill(['created_at' => now()->subDays(3)])->save();

        $failing = Mockery::mock(AnnouncementNotificationService::class);
        $failing->shouldReceive('send')->andThrow(new \RuntimeException('push down'));
        $this->app->instance(AnnouncementNotificationService::class, $failing);
        $this->assertSame(1, app(SendSurveyInvitationsUseCase::class)->handle());
        $this->assertSame(1, app(SendReservationRemindersUseCase::class)->handle());
        // 次の実行で作り直さない（重複して届かない）
        $this->assertSame(0, app(SendSurveyInvitationsUseCase::class)->handle());
        $this->assertSame(0, app(SendReservationRemindersUseCase::class)->handle());

        $pending = Announcement::withoutGlobalScopes()->where('project_id', $project->id)->get();
        $this->assertCount(2, $pending);
        $this->assertTrue($pending->every(fn (Announcement $announcement) => (bool) $announcement->notification_pending));

        // 通知は既存の再送コマンドで送り直す
        Announcement::withoutGlobalScopes()->update(['notification_retry_at' => now()->subMinute()]);
        $this->fakeNotifier(2);
        $this->artisan('announcements:retry-notifications')->assertSuccessful();
        $this->assertSame(0, Announcement::withoutGlobalScopes()->where('notification_pending', true)->count());
    }

    public function test_予約の日時を変えたらリマインドをもう一度送れるようにすること(): void
    {
        [$project, $user] = $this->member();
        $reservation = Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'reserved_at' => now()->addDay(), 'status' => ReservationStatus::CONFIRMED]);
        $reservation->forceFill(['reminder_sent_at' => now()])->save();

        $reservation->update(['notes' => 'メモだけ変更']);
        $this->assertNotNull($reservation->fresh()->reminder_sent_at);
        $reservation->update(['reserved_at' => now()->addDays(4)]);
        $this->assertNull($reservation->fresh()->reminder_sent_at);
    }

    public function test_利用機能で無効にするとメニュー_ポップアップ_アンケートを止めること(): void
    {
        [$project] = $this->member();
        PromoPopup::create(['project_id' => $project->id, 'title' => '告知', 'display_type' => 'modal', 'frequency' => 'once', 'is_active' => true]);
        $survey = $this->survey($project);
        ShopSettingStore::setValue('feature_popups_enabled', false, 'boolean', $project->id);
        ShopSettingStore::setValue('feature_surveys_enabled', false, 'boolean', $project->id);

        $this->getJson('/api/promo-popups?project_key=default')->assertOk()->assertJsonCount(0, 'popups');
        $this->getJson('/api/surveys?project_key=default')->assertOk()->assertJsonCount(0, 'surveys');
        $this->getJson("/api/surveys/{$survey->id}?project_key=default")->assertNotFound();
        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $html = $this->actingAs($admin, 'admin')->get(route('admin.settings.index', ['project_key' => 'default']))->assertOk()->getContent();
        $this->assertStringNotContainsString(route('admin.surveys.index', ['project_key' => 'default']), $html);
        $this->assertStringNotContainsString(route('admin.promo-popups.index', ['project_key' => 'default']), $html);
    }

    public function test_リマインドを2回送り本文を差し替えられること(): void
    {
        [$project, $user] = $this->member();
        ShopSettingStore::setValue('reservation_reminder_hours', 24, 'integer', $project->id);
        ShopSettingStore::setValue('reservation_reminder_second_hours', 3, 'integer', $project->id);
        ShopSettingStore::setValue('reservation_reminder_message', '{日時}のご予約です（{人数}）', 'string', $project->id);
        $reservation = Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'reserved_at' => now()->addHours(20), 'number_of_people' => 2, 'status' => ReservationStatus::CONFIRMED]);
        $reservation->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->fakeNotifier(2);

        $this->assertSame(1, app(SendReservationRemindersUseCase::class)->handle());
        $this->travel(18)->hours();
        $this->assertSame(1, app(SendReservationRemindersUseCase::class)->handle());
        $this->assertSame(0, app(SendReservationRemindersUseCase::class)->handle());

        $bodies = Announcement::withoutGlobalScopes()->where('title', 'ご予約のリマインド')->pluck('body')->all();
        $this->assertSame([$reservation->reserved_at->format('n月j日 H:i').'のご予約です（2名）'], array_values(array_unique($bodies)));
        $this->assertCount(2, $bodies);
    }

    public function test_会員にはlineとアプリをまたいで表示回数と回答済みを判定すること(): void
    {
        [$project, , $member] = $this->member();
        $survey = $this->survey($project);
        $base = ['project_id' => $project->id, 'display_type' => 'modal', 'is_active' => true, 'audience' => 'all'];
        $once = PromoPopup::create([...$base, 'title' => '1回', 'frequency' => 'once']);
        $daily = PromoPopup::create([...$base, 'title' => '毎日', 'frequency' => 'daily']);
        $everyOpen = PromoPopup::create([...$base, 'title' => '毎回', 'frequency' => 'every_open']);
        PromoPopup::create([...$base, 'title' => '回答まで', 'frequency' => 'every_open', 'link_url' => '/survey?survey='.$survey->id]);
        PromoPopup::create([...$base, 'title' => '回答後も', 'frequency' => 'every_open', 'secondary_link_url' => '/survey?survey='.$survey->id, 'hide_when_answered' => false]);
        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];
        $titles = fn () => collect($this->withHeaders($headers)->getJson('/api/promo-popups?project_key=default')->assertOk()->json('popups'))->pluck('title')->sort()->values()->all();

        // 別の端末・アプリで表示した記録として、サーバーに表示を送る
        foreach ([$once, $daily, $everyOpen] as $popup) {
            $this->withHeaders($headers)->postJson("/api/promo-popups/{$popup->id}/events?project_key=default", ['event' => 'view'])->assertOk();
        }
        $this->assertSame(['回答まで', '回答後も', '毎回'], $titles());

        $this->travel(1)->days();
        $this->assertSame(['回答まで', '回答後も', '毎回', '毎日'], $titles());
        $once->update(['title' => '1回（編集）']);
        $this->assertContains('1回（編集）', $titles());

        DB::table('survey_responses')->insert(['project_id' => $project->id, 'survey_id' => $survey->id, 'member_id' => $member->id, 'answers' => '[]', 'answered_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame(['1回（編集）', '回答後も', '毎回', '毎日'], $titles());
    }

    public function test_ポップアップの表示とタップを日ごとに数えること(): void
    {
        [$project] = $this->member();
        $popup = PromoPopup::create(['project_id' => $project->id, 'title' => '告知', 'display_type' => 'modal', 'frequency' => 'once', 'is_active' => true]);
        $other = Project::factory()->create(['is_active' => true]);
        $foreign = PromoPopup::withoutGlobalScopes()->create(['project_id' => $other->id, 'title' => '他社', 'display_type' => 'modal', 'frequency' => 'once', 'is_active' => true]);
        $url = "/api/promo-popups/{$popup->id}/events?project_key=default";

        $this->postJson($url, ['event' => 'view'])->assertOk();
        $this->postJson($url, ['event' => 'view'])->assertOk();
        $this->postJson($url, ['event' => 'click'])->assertOk();
        $this->postJson($url, ['event' => 'unknown'])->assertStatus(422);
        $this->postJson("/api/promo-popups/{$foreign->id}/events?project_key=default", ['event' => 'view'])->assertNotFound();

        $this->assertDatabaseHas('promo_popup_stats', ['promo_popup_id' => $popup->id, 'date' => now()->toDateString(), 'views' => 2, 'clicks' => 1]);
        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $this->actingAs($admin, 'admin')->get(route('admin.promo-popups.index', ['project_key' => 'default']))->assertOk()->assertSee('2 / 1（50%）');
    }

    public function test_雛形と複製と経路の開く画面とリンク候補を使えること(): void
    {
        $project = Project::firstOrCreate(['project_key' => 'default'], ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]);
        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $key = ['project_key' => 'default'];
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.surveys.create', [...$key, 'template' => 'visit']))->assertOk()->assertSee('ご来店アンケート')->assertSee('接客の満足度');
        $survey = $this->survey($project);
        $this->post(route('admin.surveys.duplicate', [...$key, 'id' => $survey->id]))->assertRedirect();
        $copy = Survey::withoutGlobalScopes()->where('title', 'ご来店アンケート（コピー）')->sole();
        $this->assertFalse($copy->is_active);
        $this->assertSame($survey->questions, $copy->questions);
        // 回答があり質問を変えられないときも、保存済みの質問をアプリのプレビューへ渡す
        $member = Member::factory()->create(['project_id' => $project->id]);
        DB::table('survey_responses')->insert(['project_id' => $project->id, 'survey_id' => $survey->id, 'member_id' => $member->id, 'answers' => '[]', 'answered_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->get(route('admin.surveys.edit', [...$key, 'id' => $survey->id]))->assertOk()
            ->assertSee('data-survey-locked="[{&quot;type&quot;:&quot;nps&quot;', false)->assertSee('id="surveyLivePreview"', false);

        $popup = PromoPopup::create(['project_id' => $project->id, 'title' => '夏', 'display_type' => 'modal', 'frequency' => 'once', 'is_active' => true]);
        $this->post(route('admin.promo-popups.duplicate', [...$key, 'id' => $popup->id]))->assertRedirect();
        $this->assertFalse(PromoPopup::withoutGlobalScopes()->where('title', '夏（コピー）')->sole()->is_active);
        $this->get(route('admin.promo-popups.create', $key))->assertOk()
            ->assertSee('<option value="/coupon">クーポン</option>', false)
            ->assertSee('value="/survey?survey='.$survey->id.'"', false)
            ->assertSee('data-promo-popup-preview', false)
            ->assertSee('id="promoPopupLivePreview"', false);

        $this->post(route('admin.acquisition-sources.store', $key), ['name' => '店頭POP', 'landing_tab' => 'coupons'])->assertRedirect();
        $html = $this->get(route('admin.qr-codes', $key))->assertOk()->assertSee('開く画面：クーポン')->getContent();
        $this->assertStringContainsString('tab=coupons', urldecode(html_entity_decode($html)));
    }

    public function test_結果画面に回答率とnps区分ごとの配信ボタンを出すこと(): void
    {
        [$project, , $member] = $this->member();
        $survey = $this->survey($project);
        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->postJson("/api/surveys/{$survey->id}/answers?project_key=default", ['answers' => [3, null, null]])->assertOk();
        $survey->responses()->update(['invited_at' => now()]);
        $tagId = MemberTag::withoutGlobalScopes()->where('name', 'NPS批判者')->value('id');

        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $html = $this->actingAs($admin, 'admin')->get(route('admin.surveys.show', ['project_key' => 'default', 'id' => $survey->id]))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/回答率（自動の回答依頼）<\/small><strong>100%<\/strong>/u', $html);
        $this->assertStringContainsString(e(route('admin.announcements.delivery.create', ['project_key' => 'default', 'delivery_type' => 'segment', 'segment_tag_ids' => [$tagId]])), $html);
    }

    public function test_二回目のレビューで直した境界の動きを守ること(): void
    {
        [$project, $user] = $this->member();
        $survey = $this->survey($project, ['questions' => [['type' => 'multi_choice', 'label' => '目的', 'options' => ['A', 'B'], 'required' => false]]]);
        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];

        // 入れ子の配列は 500 ではなく入力エラー
        $this->withHeaders($headers)->postJson("/api/surveys/{$survey->id}/answers?project_key=default", ['answers' => [[['A']]]])->assertStatus(422);
        // ポイント機能が無効なら、付かないポイントを案内しない
        ShopSettingStore::setValue('use_points', false, 'boolean', $project->id);
        $this->withHeaders($headers)->getJson("/api/surveys/{$survey->id}?project_key=default")->assertJsonPath('survey.reward_points', 0);
        // 停止中のポップアップの反応は数えない
        $stopped = PromoPopup::create(['project_id' => $project->id, 'title' => '停止中', 'display_type' => 'modal', 'frequency' => 'once', 'is_active' => false]);
        $this->postJson("/api/promo-popups/{$stopped->id}/events?project_key=default", ['event' => 'click'])->assertNotFound();
        // お知らせが無効ならリマインドを送らない
        ShopSettingStore::setValue('reservation_reminder_hours', 24, 'integer', $project->id);
        ShopSettingStore::setValue('feature_announcements_enabled', false, 'boolean', $project->id);
        $reservation = Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'reserved_at' => now()->addHours(20), 'status' => ReservationStatus::CONFIRMED]);
        $reservation->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->fakeNotifier(0);
        $this->assertSame(0, app(SendReservationRemindersUseCase::class)->handle());
        // 読んだあとに日時が変わった予約は、古い日時で送信済みにしない
        $repository = app(ReservationReminderRepository::class);
        $this->assertFalse($repository->markReminded($reservation->id, 'first', $reservation->reserved_at->copy()->subDay()));
        $this->assertTrue($repository->markReminded($reservation->id, 'first', $reservation->reserved_at));
    }

    public function test_管理画面でアンケート_ポップアップ_流入経路を作成できること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $key = ['project_key' => $project->project_key];
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.surveys.store', $key), [
            'title' => '満足度調査', 'is_active' => '1', 'reward_points' => '10', 'invite_trigger' => 'order', 'invite_delay_days' => '2',
            'questions' => [
                1000 => ['type' => 'nps', 'label' => '', 'required' => '1'],
                1001 => ['type' => 'multi_choice', 'label' => '来店目的', 'help' => 'いくつでも', 'allow_other' => '1', 'required' => '1', 'options' => [
                    ['label' => 'ランチ', 'tag' => 'ランチ利用'], ['label' => 'ディナー', 'tag' => ' ディナー利用 '], ['label' => '', 'tag' => '無視'], ['label' => 'ランチ', 'tag' => ''],
                ]],
                1002 => ['type' => 'rating', 'label' => '味'],
                1003 => ['type' => 'date', 'label' => '次の来店予定'],
            ],
        ])->assertRedirect();
        $survey = Survey::withoutGlobalScopes()->where('project_id', $project->id)->sole();
        $this->assertSame(['nps', 'multi_choice', 'rating', 'date'], array_column($survey->questions, 'type'));
        $this->assertSame(SurveyRequest::DEFAULT_NPS_LABEL, $survey->questions[0]['label']);
        $this->assertSame(['ランチ', 'ディナー'], $survey->questions[1]['options']);
        $this->assertSame(['ランチ' => 'ランチ利用', 'ディナー' => 'ディナー利用'], $survey->questions[1]['option_tags']);
        $this->assertSame(['いくつでも', true], [$survey->questions[1]['help'], $survey->questions[1]['allow_other']]);
        $this->assertSame(['order', 2], [$survey->invite_trigger, $survey->invite_delay_days]);
        $this->post(route('admin.surveys.store', $key), ['title' => '選択肢不足', 'questions' => [['type' => 'choice', 'label' => '1つだけ', 'options' => [['label' => 'A']]]]])
            ->assertSessionHasErrors('questions.0.options');
        $this->post(route('admin.surveys.store', $key), ['title' => 'NPS重複', 'questions' => [['type' => 'nps', 'label' => 'A'], ['type' => 'nps', 'label' => 'B']]])
            ->assertSessionHasErrors('questions');
        $this->get(route('admin.surveys.show', [...$key, 'id' => $survey->id]))->assertOk()->assertSee('満足度調査');
        $this->get(route('admin.surveys.create', $key))->assertOk()->assertSee('data-survey-builder', false)
            ->assertSee('id="surveyLivePreview"', false)->assertSee('#/survey', false);
        $this->get(route('admin.surveys.edit', [...$key, 'id' => $survey->id]))->assertOk()->assertSee('来店目的')
            ->assertSee('value="ランチ利用"', false)->assertSee('いくつでも');
        // 作成ボタンは画面上部のヘッダーに出す（本文の .page-header はCSSで隠れるため）
        $this->get(route('admin.surveys.index', $key))->assertOk()->assertSee('満足度調査')
            ->assertSee('href="'.route('admin.surveys.create', $key).'"', false);

        $this->post(route('admin.promo-popups.store', $key), ['title' => '危険', 'display_type' => 'modal', 'frequency' => 'once', 'audience' => 'all', 'link_url' => 'javascript:alert(1)', 'secondary_link_url' => '//evil.example.com'])
            ->assertSessionHasErrors(['link_url', 'secondary_link_url']);
        $tag = MemberTag::create(['project_id' => $project->id, 'name' => 'VIP', 'color' => '#123456']);
        $this->post(route('admin.promo-popups.store', $key), [
            'title' => '夏のキャンペーン', 'display_type' => 'banner', 'frequency' => 'daily', 'link_url' => '/coupon', 'is_active' => '1',
            'secondary_button_label' => 'あとで', 'secondary_link_url' => '/menu', 'audience' => 'segment', 'audience_tag_ids' => [$tag->id], 'audience_ranks' => ['gold'],
        ])->assertRedirect();
        $this->get(route('admin.promo-popups.index', $key))->assertOk()->assertSee('夏のキャンペーン')
            ->assertSee('href="'.route('admin.promo-popups.create', $key).'"', false);
        $this->get(route('admin.promo-popups.create', $key))->assertOk();
        $popup = PromoPopup::withoutGlobalScopes()->where('project_id', $project->id)->sole();
        $this->assertSame([[$tag->id], ['gold'], null, 'あとで'], [$popup->audience_tag_ids, $popup->audience_ranks, $popup->store_ids, $popup->secondary_button_label]);
        $this->get(route('admin.promo-popups.edit', [...$key, 'id' => $popup->id]))->assertOk()->assertSee('夏のキャンペーン')->assertSee('VIP');
        $this->get(route('admin.settings.reservations', $key))->assertOk()->assertSee('予約リマインド');

        $this->post(route('admin.acquisition-sources.store', $key), ['name' => 'チラシ'])->assertRedirect();
        $this->get(route('admin.qr-codes', $key))->assertOk()->assertSee('チラシ')
            ->assertSee('delete-form', false)->assertDontSee('confirmDialog(', false);
        // 削除の確認は共通の delete-form で出す（名前をJSの文字列に埋め込まない）
        $this->get(route('admin.promo-popups.index', $key))->assertSee('delete-form', false)->assertDontSee('confirmDialog(', false);
        $this->get(route('admin.surveys.edit', [...$key, 'id' => $survey->id]))->assertSee('delete-form', false)->assertDontSee('confirmDialog(', false);
    }
}
