<?php

namespace Tests\Feature;

use App\Enums\LotteryRewardType;
use App\Enums\LotterySourceType;
use App\Events\MemberWalletUpdated;
use App\Models\Member;
use App\Models\MemberLotteryEntitlement;
use App\Models\MemberStamp;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\User;
use App\Services\MemberLiffWalletNotifier;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LiffWalletBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000081';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createMember(Project $project, string $lineUserId = self::LINE_USER_ID): Member
    {
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);

        return Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'points' => 0,
        ]);
    }

    private function useReverbAuthConfig(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => '1001',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
            'broadcasting.connections.reverb.options.port' => 8081,
            'broadcasting.connections.reverb.options.scheme' => 'http',
            'broadcasting.connections.reverb.options.useTLS' => false,
        ]);
        app()->forgetInstance(BroadcastManager::class);
        app()->forgetInstance(BroadcastingFactory::class);
        app()->forgetInstance(Broadcaster::class);
        require base_path('routes/channels.php');
    }

    public function test_ポイント台帳の変更で会員ウォレット更新が配信されること(): void
    {
        $project = $this->createProject();
        $member = $this->createMember($project);
        Event::fake([MemberWalletUpdated::class]);

        PointTransaction::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 50,
            'description' => 'liff-wallet-broadcast',
        ]);

        Event::assertDispatched(MemberWalletUpdated::class, function (MemberWalletUpdated $event) use ($member, $project): bool {
            return $event->memberId === (int) $member->id
                && $event->projectId === (int) $project->id;
        });
    }

    public function test_スタンプ変更で会員ウォレット更新が配信されること(): void
    {
        $project = $this->createProject();
        $member = $this->createMember($project);
        $card = StampCard::factory()->create(['project_id' => $project->id, 'is_active' => true]);
        Event::fake([MemberWalletUpdated::class]);

        MemberStamp::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'stamp_card_id' => $card->id,
            'current_stamps' => 2,
        ]);

        Event::assertDispatched(MemberWalletUpdated::class, function (MemberWalletUpdated $event) use ($member): bool {
            return $event->memberId === (int) $member->id
                && $event->stampsTotal === 2;
        });
    }

    public function test_ガチャ権利の作成で会員ウォレット更新が配信されること(): void
    {
        $project = $this->createProject();
        $member = $this->createMember($project);
        Event::fake([MemberWalletUpdated::class]);

        MemberLotteryEntitlement::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::ORDER,
            'source_id' => 'broadcast-1',
            'is_done' => false,
        ]);

        Event::assertDispatched(MemberWalletUpdated::class, function (MemberWalletUpdated $event) use ($member): bool {
            return $event->memberId === (int) $member->id
                && $event->pendingLotteryCount === 1;
        });
    }

    public function test_wallet_notification_waits_for_commit_and_uses_final_balance(): void
    {
        $member = $this->createMember($this->createProject());
        Event::fake([MemberWalletUpdated::class]);
        DB::beginTransaction();
        app(MemberLiffWalletNotifier::class)->notify($member);
        $member->update(['points' => 125]);
        Event::assertNotDispatched(MemberWalletUpdated::class);
        DB::commit();
        Event::assertDispatched(MemberWalletUpdated::class, fn (MemberWalletUpdated $event): bool => $event->points === 125);
    }

    public function test_rolled_back_wallet_changes_are_not_broadcast(): void
    {
        $member = $this->createMember($this->createProject());
        Event::fake([MemberWalletUpdated::class]);
        DB::beginTransaction();
        app(MemberLiffWalletNotifier::class)->notify($member);
        $member->update(['points' => 125]);
        DB::rollBack();
        Event::assertNotDispatched(MemberWalletUpdated::class);
    }

    public function test_本人のウォレットチャネルは購読できること(): void
    {
        $project = $this->createProject();
        $member = $this->createMember($project);
        $this->useReverbAuthConfig();

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->postJson('/api/broadcasting/auth?project_key=default', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-liff.member.'.$member->id,
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_他人のウォレットチャネルは購読できないこと(): void
    {
        $project = $this->createProject();
        $this->createMember($project);
        $other = $this->createMember($project, 'U00000000000000000000000000000082');
        $this->useReverbAuthConfig();

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->postJson('/api/broadcasting/auth?project_key=default', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-liff.member.'.$other->id,
            ])
            ->assertForbidden();
    }
}
