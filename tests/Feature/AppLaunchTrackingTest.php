<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Admin\MemberSegmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** スマホアプリ・LINE（LIFF）を開いた日時の記録と、絞り込み条件「アプリの利用」 */
class AppLaunchTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000077';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_liffを開くと起動日時を記録し会員がいなければ何もしないこと(): void
    {
        $project = $this->createProject();
        Carbon::setTestNow('2026-11-12 10:00:00');
        try {
            $this->withHeaders(['X-Line-User-Id' => 'U00000000000000000000000000000099'])
                ->post('/api/app-launch?project_key=default')
                ->assertOk()->assertJsonPath('success', true);
            $this->assertSame(0, User::query()->where('line_user_id', 'U00000000000000000000000000000099')->count());

            $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::LINE_USER_ID]);
            $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
                ->post('/api/app-launch?project_key=default')
                ->assertOk();
            $this->assertSame('2026-11-12 10:00:00', $user->fresh()->last_app_launched_at?->format('Y-m-d H:i:s'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_スマホアプリの端末登録でも起動日時を記録すること(): void
    {
        $this->createProject();

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID, 'X-Device-Identifier' => 'device-launch'])
            ->post('/api/devices/register?project_key=default', ['platform' => 'ios', 'device_identifier' => 'device-launch'])
            ->assertOk();

        $this->assertNotNull(User::query()->where('line_user_id', self::LINE_USER_ID)->value('last_app_launched_at'));
    }

    public function test_アプリの利用はlineだけの会員も起動日時で判定すること(): void
    {
        $project = $this->createProject();
        $member = function (?Carbon $launchedAt) use ($project): Member {
            $user = User::factory()->create(['project_id' => $project->id]);
            $user->forceFill(['last_app_launched_at' => $launchedAt])->save();

            return Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        };
        $lineRecent = $member(now()->subDays(2));
        $lineOld = $member(now()->subDays(60));
        $neverOpened = $member(null);
        // 起動の記録が古くても、端末の利用が新しければ「利用あり」
        $appRecent = $member(now()->subDays(60));
        UserDevice::create(['project_id' => $project->id, 'user_id' => $appRecent->user_id, 'platform' => 'ios', 'device_identifier' => 'dev-recent', 'last_used_at' => now()->subDay()]);
        $segment = app(MemberSegmentService::class);
        $ids = fn (string $mode): array => $segment->getMemberIds($project->id, $segment->buildCriteriaFromInput([
            'segment_app_last_used_mode' => $mode,
            'segment_app_last_used_days' => '30',
        ]) ?? [])->all();

        $this->assertEqualsCanonicalizing([$lineRecent->id, $appRecent->id], $ids('within_days'));
        $this->assertEqualsCanonicalizing([$lineOld->id], $ids('inactive_days'));
        $this->assertNotContains($neverOpened->id, [...$ids('within_days'), ...$ids('inactive_days')]);
    }
}
