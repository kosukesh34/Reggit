<?php

namespace Tests\Feature;

use App\Domain\Weather\ValueObjects\HourlyForecast;
use App\Enums\CouponDeliveryType;
use App\Mail\WeatherCouponApprovalMail;
use App\Models\AdminUser;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Models\Store;
use App\Models\WeatherCouponRule;
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

    private function rule(array $attributes = []): WeatherCouponRule
    {
        return WeatherCouponRule::withoutGlobalScopes()->create($attributes + [
            'project_id' => $this->project->id,
            'store_id' => $this->store->id,
            'coupon_id' => $this->coupon->id,
            'min_precipitation_probability' => 50,
            'min_rain_hours' => 2,
            'check_hours_before_open' => 1,
            'cutoff_hours_before_close' => 2,
            'weekly_limit' => 2,
            'target' => WeatherCouponRule::TARGET_STORE_MEMBERS,
            'requires_approval' => false,
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
            fn (int $hour, int $probability) => new HourlyForecast(CarbonImmutable::parse('2026-11-02')->setTime($hour, 0), $probability, 1.0),
            array_keys($probabilities),
            array_values($probabilities),
        );
        $this->mock(OpenWeatherClient::class)->shouldReceive('hourly')->times($times)->andReturn($forecasts);
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
        $this->assertDatabaseHas('weather_coupon_runs', ['weather_coupon_rule_id' => $rule->id, 'status' => 'sent', 'coupon_delivery_id' => $delivery->id, 'max_precipitation_probability' => 80]);
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
        WeatherCouponRun::withoutGlobalScopes()->create(['project_id' => $this->project->id, 'weather_coupon_rule_id' => $rule->id, 'business_date' => '2026-11-02', 'status' => 'sent', 'issued_count' => 3]);
        $this->forecast([12 => 80, 13 => 80], 0);

        $this->assertSame([WeatherCouponRun::STATUS_LIMIT_REACHED => 1], $this->runAt('2026-11-03 09:30'));
        $this->assertSame(0, MemberCoupon::withoutGlobalScopes()->count());
    }

    public function test_対象が0人だった日は週の上限に数えないこと(): void
    {
        $this->member();
        $rule = $this->rule(['weekly_limit' => 1]);
        WeatherCouponRun::withoutGlobalScopes()->create(['project_id' => $this->project->id, 'weather_coupon_rule_id' => $rule->id, 'business_date' => '2026-11-01', 'status' => 'sent', 'issued_count' => 0]);
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

    public function test_利用停止の店舗と座標のない店舗は判定しないこと(): void
    {
        $this->forecast([], 0);
        $inactive = $this->rule();
        $this->store->update(['is_active' => false]);
        $this->assertSame([], $this->runAt('2026-11-02 09:30'));
        $inactive->delete();
        $this->store->update(['is_active' => true]);

        $this->rule();
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
        $this->rule(['requires_approval' => true]);
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
        $this->rule(['requires_approval' => true]);
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
        $this->actingAs($admin, 'admin')->get('/admin/rain/weather-coupons')->assertOk()->assertSee('承認して配る');
    }

    public function test_クーポンの管理権限だけのスタッフも一覧を開けること(): void
    {
        $staff = AdminUser::factory()->create(['role' => AdminUser::ROLE_STAFF, 'project_id' => $this->project->id]);
        $staff->projects()->sync([$this->project->id]);
        $staff->stores()->sync([$this->store->id => ['project_id' => $this->project->id]]);
        $staff->permissions()->create(['permission' => 'coupons.manage']);

        $this->actingAs($staff, 'admin')->get('/admin/rain/weather-coupons')->assertOk();
    }
}
