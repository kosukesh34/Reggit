<?php

namespace Tests\Feature;

use App\Domain\Weather\ValueObjects\HourlyForecast;
use App\Enums\CouponDeliveryType;
use App\Enums\MemberCouponSource;
use App\Enums\ScheduledDeliveryContentType;
use App\Enums\ScheduledDeliveryType;
use App\Mail\WeatherCouponApprovalMail;
use App\Models\AdminUser;
use App\Models\AfterRegistrationDelivery;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Models\Store;
use App\Models\WeatherCouponRun;
use App\Services\Weather\OpenWeatherClient;
use App\Services\WeatherCouponService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WeatherCouponServiceTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private Store $store;

    private Coupon $coupon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create(['project_key' => 'rain', 'name' => 'Rain', 'is_active' => true]);
        $this->store = $this->project->stores()->firstOrFail();
        $this->store->update(['latitude' => 33.5494032, 'longitude' => 130.4241976]);
        // 2店舗目があると会員が自動で店舗に紐づかないため、紐づけをテストで制御できる
        Store::create(['project_id' => $this->project->id, 'store_key' => 'other', 'name' => '別店舗', 'is_active' => true]);
        $this->coupon = Coupon::factory()->create(['project_id' => $this->project->id, 'end_at' => now()->addMonth()]);
    }

    /** スケジュール配信の「雨の日」（初期値はクーポンのみ・承認なし） */
    private function rule(array $params = [], array $attributes = []): AfterRegistrationDelivery
    {
        return AfterRegistrationDelivery::withoutGlobalScopes()->create($attributes + [
            'project_id' => $this->project->id,
            'schedule_type' => ScheduledDeliveryType::WEATHER_RAIN,
            'content_type' => ScheduledDeliveryContentType::COUPON_ONLY,
            'coupon_id' => $this->coupon->id,
            'send_notification' => true,
            'is_active' => true,
            'params' => $params + [
                'store_id' => $this->store->id,
                'min_precipitation_probability' => 50,
                'min_rain_hours' => 2,
                'check_hours_before_open' => 1,
                'cutoff_hours_before_close' => 2,
                'weekly_limit' => 2,
                'target' => AfterRegistrationDelivery::WEATHER_TARGET_STORE_MEMBERS,
                'requires_approval' => 0,
            ],
        ]);
    }

    private function member(bool $atStore = true): Member
    {
        $member = Member::factory()->create(['project_id' => $this->project->id]);
        if ($atStore) {
            $member->stores()->attach($this->store->id, ['project_id' => $this->project->id]);
        }

        return $member;
    }

    /** @param array<int, int> $probabilities 時 => 降水確率（2026-11-02） */
    private function forecast(array $probabilities, int $times = 1): void
    {
        $forecasts = array_map(
            fn (int $hour, int $probability) => new HourlyForecast(CarbonImmutable::parse('2026-11-02')->setTime($hour, 0), $probability, 1.0, 20.0),
            array_keys($probabilities),
            array_values($probabilities),
        );
        $this->mock(OpenWeatherClient::class)->shouldReceive('hourly')->times($times)->andReturn($forecasts);
    }

    /** @param array<int, float> $temperatures 時 => 気温（2026-11-02） */
    private function temperatures(array $temperatures): void
    {
        $forecasts = array_map(
            fn (int $hour, float $temperature) => new HourlyForecast(CarbonImmutable::parse('2026-11-02')->setTime($hour, 0), 0, 0.0, $temperature),
            array_keys($temperatures),
            array_values($temperatures),
        );
        $this->mock(OpenWeatherClient::class)->shouldReceive('hourly')->once()->andReturn($forecasts);
    }

    /** スケジュール配信の「気温」（初期値は最高気温30℃以上・クーポンのみ・承認なし） */
    private function temperatureRule(array $params = []): AfterRegistrationDelivery
    {
        return $this->rule($params + [
            'temperature_condition' => AfterRegistrationDelivery::TEMPERATURE_ABOVE,
            'temperature' => 30,
        ], ['schedule_type' => ScheduledDeliveryType::WEATHER_TEMPERATURE]);
    }

    private function runAt(string $moment): array
    {
        $this->travelTo(CarbonImmutable::parse($moment));

        return app(WeatherCouponService::class)->runDue(CarbonImmutable::now());
    }

    public function test_雨なら店舗の会員だけに営業日限りのクーポンを1回だけ配ること(): void
    {
        $atStore = $this->member();
        $other = $this->member(false);
        $rule = $this->rule();
        $this->forecast([12 => 80, 13 => 70]);

        $this->assertSame([WeatherCouponRun::STATUS_SENT => 1], $this->runAt('2026-11-02 09:30'));
        $this->assertSame([], $this->runAt('2026-11-02 11:00'));

        $issued = MemberCoupon::withoutGlobalScopes()->where('coupon_id', $this->coupon->id)->get();
        $this->assertSame([$atStore->id], $issued->pluck('member_id')->all());
        $this->assertSame('2026-11-02 23:59:59', $issued->first()->valid_until->format('Y-m-d H:i:s'));
        $this->assertFalse(MemberCoupon::withoutGlobalScopes()->where('member_id', $other->id)->exists());
        $delivery = CouponDelivery::withoutGlobalScopes()->sole();
        $this->assertSame([CouponDeliveryType::EVENT_WEATHER_RAIN, 1], [$delivery->delivery_type, $delivery->issued_count]);
        $this->assertDatabaseHas('weather_coupon_runs', ['after_registration_delivery_id' => $rule->id, 'status' => 'sent', 'coupon_delivery_id' => $delivery->id, 'max_precipitation_probability' => 80]);
    }

    public function test_雨でなければ配らず1時間は判定し直さないこと(): void
    {
        $this->member();
        $this->rule();
        $this->forecast([12 => 80, 13 => 30]);

        $this->assertSame([WeatherCouponRun::STATUS_NO_RAIN => 1], $this->runAt('2026-11-02 09:30'));
        $this->assertSame([], $this->runAt('2026-11-02 10:15'));

        $this->assertSame(0, MemberCoupon::withoutGlobalScopes()->count());
        $this->assertDatabaseHas('weather_coupon_runs', ['status' => 'no_rain', 'max_precipitation_probability' => 80]);
    }

    public function test_判定時刻の前と締め切りの後と休業日は判定しないこと(): void
    {
        $this->rule();
        $this->forecast([12 => 80, 13 => 80], 0);

        $this->assertSame([], $this->runAt('2026-11-02 08:59'));
        $this->assertSame([], $this->runAt('2026-11-02 20:00'));
        $this->store->update(['settings' => ['closed_days' => [1]]]);
        $this->assertSame([], $this->runAt('2026-11-02 12:00'));
        $this->assertSame(0, WeatherCouponRun::withoutGlobalScopes()->count());
    }

    public function test_日をまたぐ営業は前日の営業日として翌日の閉店まで有効にすること(): void
    {
        $this->member();
        $this->store->update(['settings' => ['business_hours_start' => '18:00', 'business_hours_end' => '03:00']]);
        $this->rule(['cutoff_hours_before_close' => 0]);
        $this->forecast([0 => 90, 1 => 90, 2 => 90]);

        $this->assertSame([WeatherCouponRun::STATUS_SENT => 1], $this->runAt('2026-11-02 00:10'));

        $this->assertDatabaseHas('weather_coupon_runs', ['business_date' => '2026-11-01 00:00:00']);
        $this->assertSame('2026-11-02 02:59:59', MemberCoupon::withoutGlobalScopes()->sole()->valid_until->format('Y-m-d H:i:s'));
    }

    public function test_週の上限に達したら配らないこと(): void
    {
        $this->member();
        $rule = $this->rule(['weekly_limit' => 1]);
        WeatherCouponRun::withoutGlobalScopes()->create(['project_id' => $this->project->id, 'after_registration_delivery_id' => $rule->id, 'business_date' => '2026-11-02', 'status' => 'sent', 'issued_count' => 3]);
        $this->forecast([12 => 80, 13 => 80], 0);

        $this->assertSame([WeatherCouponRun::STATUS_LIMIT_REACHED => 1], $this->runAt('2026-11-03 09:30'));
        $this->assertSame(0, MemberCoupon::withoutGlobalScopes()->count());
    }

    public function test_対象が0人だった日は週の上限に数えないこと(): void
    {
        $this->member();
        $rule = $this->rule(['weekly_limit' => 1]);
        WeatherCouponRun::withoutGlobalScopes()->create(['project_id' => $this->project->id, 'after_registration_delivery_id' => $rule->id, 'business_date' => '2026-11-01', 'status' => 'sent', 'issued_count' => 0]);
        $this->forecast([12 => 80, 13 => 80]);

        $this->assertSame([WeatherCouponRun::STATUS_SENT => 1], $this->runAt('2026-11-02 09:30'));
        $this->assertSame(1, MemberCoupon::withoutGlobalScopes()->count());
    }

    public function test_対象の会員がいなければ誰にも配らないこと(): void
    {
        $this->member(false);
        $this->rule();
        $this->forecast([12 => 80, 13 => 80]);

        $this->assertSame([WeatherCouponRun::STATUS_SENT => 1], $this->runAt('2026-11-02 09:30'));
        $this->assertSame(0, MemberCoupon::withoutGlobalScopes()->count());
    }

    public function test_利用停止の店舗と停止中のクーポンと座標のない店舗は判定しないこと(): void
    {
        $this->forecast([], 0);
        $inactive = $this->rule();
        $this->store->update(['is_active' => false]);
        $this->assertSame([], $this->runAt('2026-11-02 09:30'));
        $inactive->delete();
        $this->store->update(['is_active' => true]);

        $this->rule();
        $this->coupon->update(['is_active' => false]);
        $this->assertSame([], $this->runAt('2026-11-02 09:30'));
        $this->coupon->update(['is_active' => true]);

        $this->store->update(['latitude' => null, 'longitude' => null]);
        $this->assertSame([], $this->runAt('2026-11-02 09:30'));
    }

    private function admin(?Store $store = null): AdminUser
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $this->project->id, 'email' => 'store'.uniqid().'@example.com']);
        $admin->projects()->sync([$this->project->id]);
        $admin->stores()->sync([($store ?? $this->store)->id => ['project_id' => $this->project->id]]);

        return $admin;
    }

    private function awaitingRun(): WeatherCouponRun
    {
        $this->rule(['requires_approval' => 1]);
        $this->runAt('2026-11-02 09:30');

        return WeatherCouponRun::withoutGlobalScopes()->sole();
    }

    public function test_承認制なら配らずに店舗の担当者へ承認のメールを送ること(): void
    {
        Mail::fake();
        $this->member();
        $storeAdmin = $this->admin();
        $this->admin();
        $otherAdmin = $this->admin(Store::where('store_key', 'other')->firstOrFail());
        $this->rule(['requires_approval' => 1]);
        $this->forecast([12 => 80, 13 => 80]);

        $this->assertSame([WeatherCouponRun::STATUS_AWAITING_APPROVAL => 1], $this->runAt('2026-11-02 09:30'));

        $this->assertSame(0, MemberCoupon::withoutGlobalScopes()->count());
        Mail::assertSent(WeatherCouponApprovalMail::class, fn ($mail) => $mail->hasTo($storeAdmin->email) && $mail->expiresAt === '11月2日 10:00');
        Mail::assertSent(WeatherCouponApprovalMail::class, 2);
        Mail::assertNotSent(WeatherCouponApprovalMail::class, fn ($mail) => count($mail->to) !== 1);
        Mail::assertNotSent(WeatherCouponApprovalMail::class, fn ($mail) => $mail->hasTo($otherAdmin->email));
    }

    public function test_期限内に承認するとすぐに配ること(): void
    {
        Mail::fake();
        $member = $this->member();
        $this->forecast([12 => 80, 13 => 80]);
        $run = $this->awaitingRun();
        $this->travelTo(CarbonImmutable::parse('2026-11-02 09:50'));

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/rain/weather-coupons/runs/'.$run->id.'/approve')
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame(WeatherCouponRun::STATUS_SENT, $run->fresh()->status);
        $this->assertSame($member->id, MemberCoupon::withoutGlobalScopes()->sole()->member_id);
    }

    public function test_全会員宛てを本部が承認すると選択中の店舗に関係なく全会員に配ること(): void
    {
        [$atStore, $atOtherStore, $run] = $this->allMembersRunAwaitingApproval();

        $this->actingAs($this->projectAdmin(), 'admin')
            ->post('/admin/rain/weather-coupons/runs/'.$run->id.'/approve', ['store_id' => $this->store->id])
            ->assertSessionHas('success');

        $this->assertEqualsCanonicalizing([$atStore->id, $atOtherStore->id], MemberCoupon::withoutGlobalScopes()->pluck('member_id')->all());
    }

    public function test_全会員宛てを店舗の担当者が承認すると担当店舗の会員だけに配ること(): void
    {
        [$atStore, , $run] = $this->allMembersRunAwaitingApproval();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/rain/weather-coupons/runs/'.$run->id.'/approve')
            ->assertSessionHas('success');

        $this->assertSame([$atStore->id], MemberCoupon::withoutGlobalScopes()->pluck('member_id')->all());
    }

    /** @return array{0: Member, 1: Member, 2: WeatherCouponRun} この店舗の会員、別店舗の会員、承認待ちの判定結果 */
    private function allMembersRunAwaitingApproval(): array
    {
        Mail::fake();
        $atStore = $this->member();
        $atOtherStore = $this->member(false);
        $atOtherStore->stores()->attach(Store::where('store_key', 'other')->firstOrFail()->id, ['project_id' => $this->project->id]);
        $this->forecast([12 => 80, 13 => 80]);
        $this->rule(['requires_approval' => 1, 'target' => AfterRegistrationDelivery::WEATHER_TARGET_ALL_MEMBERS]);
        $this->runAt('2026-11-02 09:30');
        $this->travelTo(CarbonImmutable::parse('2026-11-02 09:50'));

        return [$atStore, $atOtherStore, WeatherCouponRun::withoutGlobalScopes()->sole()];
    }

    public function test_期限切れや他店舗の担当者は承認できないこと(): void
    {
        Mail::fake();
        $this->member();
        $this->forecast([12 => 80, 13 => 80]);
        $run = $this->awaitingRun();

        $this->actingAs($this->admin(Store::where('store_key', 'other')->firstOrFail()), 'admin')
            ->post('/admin/rain/weather-coupons/runs/'.$run->id.'/approve')->assertNotFound();
        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00'));
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/rain/weather-coupons/runs/'.$run->id.'/approve')->assertSessionHas('error');

        $this->assertSame(0, MemberCoupon::withoutGlobalScopes()->count());
    }

    public function test_今日は配らないを選ぶとその日は判定もメールも止めること(): void
    {
        Mail::fake();
        $this->forecast([12 => 80, 13 => 80]);
        $run = $this->awaitingRun();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/rain/weather-coupons/runs/'.$run->id.'/decline')->assertSessionHas('success');
        $this->assertSame([], $this->runAt('2026-11-02 11:00'));

        $this->assertSame(WeatherCouponRun::STATUS_DECLINED, $run->fresh()->status);
        Mail::assertSentCount(0);
    }

    public function test_承認されないまま期限が切れたら次の判定で再び承認を求めること(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $this->forecast([12 => 80, 13 => 80], 2);
        $run = $this->awaitingRun();

        $this->assertSame([], $this->runAt('2026-11-02 10:05'));
        $this->assertSame(WeatherCouponRun::STATUS_EXPIRED, $run->fresh()->status);
        $this->assertSame([WeatherCouponRun::STATUS_AWAITING_APPROVAL => 1], $this->runAt('2026-11-02 10:30'));

        Mail::assertSent(WeatherCouponApprovalMail::class, 2);
        $this->actingAs($admin, 'admin')->get('/admin/rain/weather-coupons')->assertOk()->assertSee('承認して送る');
    }

    public function test_お知らせの管理権限だけのスタッフも一覧を開けること(): void
    {
        $staff = AdminUser::factory()->create(['role' => AdminUser::ROLE_STAFF, 'project_id' => $this->project->id]);
        $staff->projects()->sync([$this->project->id]);
        $staff->stores()->sync([$this->store->id => ['project_id' => $this->project->id]]);
        $staff->permissions()->create(['permission' => 'announcements.manage']);

        $this->actingAs($staff, 'admin')->get('/admin/rain/weather-coupons')->assertOk();
    }

    /** @return array<string, mixed> */
    private function scheduledInput(array $overrides = []): array
    {
        return $overrides + [
            'category' => 'condition',
            'schedule_type' => ScheduledDeliveryType::WEATHER_RAIN,
            'content_type' => ScheduledDeliveryContentType::COUPON_ONLY,
            'coupon_id' => $this->coupon->id,
            'send_notification' => '1',
            'is_active' => '1',
            'store_id' => $this->store->id,
            'min_precipitation_probability' => 60,
            'min_rain_hours' => 3,
            'check_hours_before_open' => 1,
            'cutoff_hours_before_close' => 2,
            'weekly_limit' => 2,
            'weather_target' => AfterRegistrationDelivery::WEATHER_TARGET_STORE_MEMBERS,
            'requires_approval' => '1',
        ];
    }

    private function projectAdmin(): AdminUser
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_PROJECT_ADMIN, 'project_id' => $this->project->id]);
        $admin->projects()->sync([$this->project->id]);

        return $admin;
    }

    public function test_スケジュール配信で雨の日を登録して編集と削除ができること(): void
    {
        $this->actingAs($this->projectAdmin(), 'admin')->post('/admin/rain/after-registration-deliveries', $this->scheduledInput())
            ->assertSessionHasNoErrors()->assertRedirect();
        $delivery = AfterRegistrationDelivery::withoutGlobalScopes()->sole();
        $this->assertSame(ScheduledDeliveryType::WEATHER_RAIN, $delivery->schedule_type);
        $this->assertSame(
            [$this->store->id, 60, 3, 1, 2, 2, AfterRegistrationDelivery::WEATHER_TARGET_STORE_MEMBERS, 1],
            [$delivery->param('store_id'), $delivery->param('min_precipitation_probability'), $delivery->param('min_rain_hours'), $delivery->param('check_hours_before_open'), $delivery->param('cutoff_hours_before_close'), $delivery->param('weekly_limit'), $delivery->param('target'), $delivery->param('requires_approval')],
        );
        $this->get('/admin/rain/after-registration-deliveries')->assertOk()->assertSee('降水確率60%以上が3時間続く日')->assertSee('天気の判定結果');
        $this->get('/admin/rain/after-registration-deliveries/'.$delivery->id.'/edit')->assertOk()->assertSee('送る前にメールで承認を求める');

        $this->put('/admin/rain/after-registration-deliveries/'.$delivery->id, $this->scheduledInput(['weekly_limit' => 5, 'requires_approval' => '0']))
            ->assertSessionHasNoErrors();
        $this->assertSame([5, 0], [$delivery->fresh()->param('weekly_limit'), $delivery->fresh()->param('requires_approval')]);

        $this->delete('/admin/rain/after-registration-deliveries/'.$delivery->id)->assertRedirect();
        $this->assertSame(0, AfterRegistrationDelivery::withoutGlobalScopes()->count());
    }

    public function test_雨の日の閾値は必須で営業時間に収まらなければ保存しないこと(): void
    {
        $this->actingAs($this->projectAdmin(), 'admin');

        $this->post('/admin/rain/after-registration-deliveries', $this->scheduledInput(['min_precipitation_probability' => '', 'weekly_limit' => 8]))
            ->assertSessionHasErrors(['min_precipitation_probability', 'weekly_limit']);
        // 初期設定の営業時間は 10:00〜22:00 の12時間
        $this->post('/admin/rain/after-registration-deliveries', $this->scheduledInput(['min_rain_hours' => 13, 'cutoff_hours_before_close' => 12]))
            ->assertSessionHasErrors(['min_rain_hours', 'cutoff_hours_before_close']);

        $this->assertSame(0, AfterRegistrationDelivery::withoutGlobalScopes()->count());
    }

    public function test_店舗の担当者はスケジュール配信の雨の日を登録できないこと(): void
    {
        $this->actingAs($this->admin(Store::where('store_key', 'other')->firstOrFail()), 'admin');

        $this->post('/admin/rain/after-registration-deliveries', $this->scheduledInput())->assertForbidden();

        $this->assertSame(0, AfterRegistrationDelivery::withoutGlobalScopes()->count());
    }

    public function test_お知らせとクーポンを選ぶとお知らせ1件と通知なしのクーポンを送ること(): void
    {
        $member = $this->member();
        $this->rule([], ['content_type' => ScheduledDeliveryContentType::ANNOUNCEMENT, 'title' => '雨の日限定10%OFF', 'body' => '本日中に使えます']);
        $this->forecast([12 => 80, 13 => 80]);

        $this->assertSame([WeatherCouponRun::STATUS_SENT => 1], $this->runAt('2026-11-02 09:30'));

        $announcement = Announcement::withoutGlobalScopes()->sole();
        $this->assertSame(['雨の日限定10%OFF', [$member->id], $this->coupon->id], [$announcement->title, $announcement->target_member_ids, $announcement->delivery_coupon_id]);
        $this->assertSame($member->id, MemberCoupon::withoutGlobalScopes()->sole()->member_id);
    }

    public function test_配った営業日のうちに登録した店舗の会員にも配ること(): void
    {
        $this->member();
        $this->rule();
        $this->forecast([12 => 80, 13 => 80]);
        $this->runAt('2026-11-02 09:30');
        $newcomer = $this->member();
        $elsewhere = $this->member(false);
        $service = app(WeatherCouponService::class);

        $this->travelTo(CarbonImmutable::parse('2026-11-02 15:00'));
        $service->issueForNewMember($newcomer, CarbonImmutable::now());
        $service->issueForNewMember($newcomer, CarbonImmutable::now());
        $service->issueForNewMember($elsewhere, CarbonImmutable::now());
        $nextDay = $this->member();
        $service->issueForNewMember($nextDay, CarbonImmutable::parse('2026-11-03 09:00'));

        $issued = MemberCoupon::withoutGlobalScopes()->where('member_id', $newcomer->id)->sole();
        $this->assertSame('2026-11-02 23:59:59', $issued->valid_until->format('Y-m-d H:i:s'));
        $this->assertSame(0, MemberCoupon::withoutGlobalScopes()->whereIn('member_id', [$elsewhere->id, $nextDay->id])->count());
        $this->assertSame(2, WeatherCouponRun::withoutGlobalScopes()->sole()->issued_count);
        $this->assertSame(2, CouponDelivery::withoutGlobalScopes()->sole()->issued_count);
    }

    public function test_配信期間の外では雨の日も判定しないこと(): void
    {
        $this->member();
        $this->rule([], ['starts_at' => '2026-11-02 09:45:00']);
        $this->forecast([12 => 80, 13 => 80], 0);

        $this->assertSame([], $this->runAt('2026-11-02 09:30'));
        $this->assertSame(0, WeatherCouponRun::withoutGlobalScopes()->count());
    }

    public function test_営業時間内の最高気温が基準以上なら気温クーポンを配ること(): void
    {
        $member = $this->member();
        $rule = $this->temperatureRule();
        // 開店（10:00）前の暑さは数えない
        $this->temperatures([8 => 35.0, 12 => 29.0, 15 => 31.24]);

        $this->assertSame([WeatherCouponRun::STATUS_SENT => 1], $this->runAt('2026-11-02 09:30'));

        $issued = MemberCoupon::withoutGlobalScopes()->sole();
        $this->assertSame([$member->id, MemberCouponSource::WEATHER_TEMPERATURE], [$issued->member_id, $issued->issue_source_type]);
        $this->assertSame('2026-11-02 23:59:59', $issued->valid_until->format('Y-m-d H:i:s'));
        $this->assertSame(CouponDeliveryType::EVENT_WEATHER_TEMPERATURE, CouponDelivery::withoutGlobalScopes()->sole()->delivery_type);
        $this->assertDatabaseHas('weather_coupon_runs', ['after_registration_delivery_id' => $rule->id, 'status' => 'sent', 'forecast_temperature' => 31.2, 'max_precipitation_probability' => null]);
    }

    public function test_営業時間内の最低気温が基準以下にならなければ配らないこと(): void
    {
        $this->member();
        $this->temperatureRule(['temperature_condition' => AfterRegistrationDelivery::TEMPERATURE_BELOW, 'temperature' => 5]);
        $this->temperatures([9 => 2.0, 12 => 6.0, 18 => 5.5]);

        $this->assertSame([WeatherCouponRun::STATUS_NOT_MET => 1], $this->runAt('2026-11-02 09:30'));

        $this->assertSame(0, MemberCoupon::withoutGlobalScopes()->count());
        $run = WeatherCouponRun::withoutGlobalScopes()->with('delivery')->sole();
        $this->assertSame([WeatherCouponRun::STATUS_NOT_MET, '最低気温 5.5℃'], [$run->status, $run->forecastLabel()]);
    }

    public function test_気温の承認メールに判定に使った気温を載せること(): void
    {
        Mail::fake();
        $this->member();
        $storeAdmin = $this->admin();
        $this->temperatureRule(['temperature_condition' => AfterRegistrationDelivery::TEMPERATURE_BELOW, 'temperature' => 5, 'requires_approval' => 1]);
        $this->temperatures([12 => 4.0, 18 => 3.5]);

        $this->assertSame([WeatherCouponRun::STATUS_AWAITING_APPROVAL => 1], $this->runAt('2026-11-02 09:30'));

        Mail::assertSent(WeatherCouponApprovalMail::class, fn ($mail) => $mail->hasTo($storeAdmin->email)
            && [$mail->typeLabel, $mail->forecast] === ['気温', '最低気温 3.5℃']
            && $mail->envelope()->subject === '【承認のお願い】'.$this->store->name.' の気温の配信');
    }

    public function test_スケジュール配信で気温を登録でき気温の条件は必須であること(): void
    {
        $this->actingAs($this->projectAdmin(), 'admin');
        $input = $this->scheduledInput(['schedule_type' => ScheduledDeliveryType::WEATHER_TEMPERATURE, 'temperature_condition' => AfterRegistrationDelivery::TEMPERATURE_BELOW, 'temperature' => -3]);

        $this->post('/admin/rain/after-registration-deliveries', ['temperature' => '', 'temperature_condition' => 'hot'] + $input)
            ->assertSessionHasErrors(['temperature', 'temperature_condition']);
        $this->post('/admin/rain/after-registration-deliveries', $input)->assertSessionHasNoErrors()->assertRedirect();

        $delivery = AfterRegistrationDelivery::withoutGlobalScopes()->sole();
        $this->assertSame([AfterRegistrationDelivery::TEMPERATURE_BELOW, -3, null], [$delivery->param('temperature_condition'), $delivery->param('temperature'), $delivery->param('min_precipitation_probability')]);
        $this->get('/admin/rain/after-registration-deliveries')->assertOk()->assertSee('最低気温が-3℃以下の日')->assertSee('天気の判定結果');
        $this->get('/admin/rain/after-registration-deliveries/'.$delivery->id.'/edit')->assertOk()->assertSee('最低気温が基準以下（寒い日）');
    }
}
