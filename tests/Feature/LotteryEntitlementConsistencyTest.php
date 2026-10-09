<?php

namespace Tests\Feature;

use App\Enums\LotteryGrantTrigger;
use App\Enums\LotteryRewardType;
use App\Enums\LotterySourceType;
use App\Models\Coupon;
use App\Models\LotteryPrize;
use App\Models\Member;
use App\Models\MemberLotteryEntitlement;
use App\Models\Project;
use App\Models\Settings\ProjectLotterySetting;
use App\Models\User;
use App\Services\Lottery\LotteryEntitlementService;
use App\Services\Lottery\LotteryPlayService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LotteryEntitlementConsistencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_concurrent_different_purchases_grant_only_one_daily_entitlement(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires MySQL and pcntl for independent concurrent connections.');
        }

        $member = $this->memberWithSettings(LotteryGrantTrigger::ONCE_PER_DAY);
        $directory = sys_get_temp_dir().'/lottery-entitlement-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        DB::disconnect();
        $workers = [];
        try {
            foreach ([0, 1] as $index) {
                $pid = pcntl_fork();
                $this->assertNotSame(-1, $pid);
                if ($pid === 0) {
                    try {
                        DB::purge();
                        // READ COMMITTED prevents gap locks from masking a missing member lock.
                        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
                        DB::statement('SET SESSION innodb_lock_wait_timeout = 5');
                        $firstRead = true;
                        DB::listen(function ($query) use ($directory, $index, &$firstRead): void {
                            if (! $firstRead || ! str_contains($query->sql, 'from `member_lottery_entitlements`')) {
                                return;
                            }
                            $firstRead = false;
                            file_put_contents($directory.'/read-'.$index, 'ready');
                            if ($index === 0) {
                                $this->waitUntil(fn () => is_file($directory.'/release'));
                            }
                        });
                        file_put_contents($directory.'/connection-'.$index, (string) DB::selectOne('SELECT CONNECTION_ID() AS id')->id);
                        app(LotteryEntitlementService::class)->grantForPurchase(
                            $member, 1000, LotterySourceType::SMAREGI_TRANSACTION, 'concurrent-purchase-'.$index,
                        );
                        file_put_contents($directory.'/result-'.$index, 'ok');
                        exit(0);
                    } catch (\Throwable $e) {
                        file_put_contents($directory.'/result-'.$index, get_class($e).': '.$e->getMessage());
                        exit(1);
                    }
                }
                $workers[] = $pid;
                if ($index === 0) {
                    $this->waitUntil(fn () => is_file($directory.'/read-0'));
                }
            }
            $this->waitUntil(fn () => is_file($directory.'/connection-1'));
            $connectionId = (int) file_get_contents($directory.'/connection-1');
            $this->waitUntil(function () use ($directory, $connectionId): bool {
                if (is_file($directory.'/read-1')) {
                    return true;
                }

                return DB::selectOne(
                    'SELECT COUNT(*) AS waiting FROM information_schema.innodb_trx WHERE trx_mysql_thread_id = ? AND trx_state = ?',
                    [$connectionId, 'LOCK WAIT'],
                )->waiting > 0;
            });
            file_put_contents($directory.'/release', 'release');
            foreach ($workers as $pid) {
                $status = 0;
                $this->waitUntil(fn () => pcntl_waitpid($pid, $status, WNOHANG) === $pid);
            }
            $workers = [];
            foreach ([0, 1] as $index) {
                $this->assertSame('ok', file_get_contents($directory.'/result-'.$index));
            }
            $this->assertSame(1, MemberLotteryEntitlement::where('member_id', $member->id)->count());
        } finally {
            file_put_contents($directory.'/release', 'release');
            foreach ($workers as $pid) {
                posix_kill($pid, SIGKILL);
                pcntl_waitpid($pid, $status);
            }
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
            DB::purge();
            $this->truncateDatabaseTables();
        }
    }

    public function test_daily_limit_sees_a_grant_committed_after_an_outer_snapshot(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL repeatable-read snapshots.');
        }

        $member = $this->memberWithSettings(LotteryGrantTrigger::ONCE_PER_DAY);
        $default = DB::getDefaultConnection();
        config(['database.connections.lottery_writer' => config('database.connections.'.$default)]);
        $writer = DB::connection('lottery_writer');
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        DB::beginTransaction();
        try {
            $this->assertSame(0, MemberLotteryEntitlement::where('member_id', $member->id)->count());
            $writer->table('member_lottery_entitlements')->insert([
                'project_id' => $member->project_id, 'member_id' => $member->id,
                'reward_type' => LotteryRewardType::GACHA,
                'source_type' => LotterySourceType::SMAREGI_TRANSACTION, 'source_id' => 'first-purchase',
                'is_done' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->assertSame(0, MemberLotteryEntitlement::where('member_id', $member->id)->count());
            app(LotteryEntitlementService::class)->grantForPurchase(
                $member, 1000, LotterySourceType::SMAREGI_TRANSACTION, 'second-purchase',
            );
            DB::commit();
            $this->assertSame(1, MemberLotteryEntitlement::where('member_id', $member->id)->count());
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('lottery_writer');
        }
    }

    public function test_winning_play_and_purchase_use_a_consistent_member_lock_order(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires MySQL and pcntl for independent concurrent connections.');
        }

        $member = $this->memberWithSettings(LotteryGrantTrigger::ONCE_PER_DAY);
        $coupon = Coupon::factory()->create(['project_id' => $member->project_id, 'is_active' => true]);
        LotteryPrize::create([
            'project_id' => $member->project_id, 'reward_type' => LotteryRewardType::GACHA,
            'coupon_id' => $coupon->id, 'probability' => 100, 'is_active' => true, 'is_lose' => false,
        ]);
        $entitlement = MemberLotteryEntitlement::create([
            'project_id' => $member->project_id, 'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA, 'source_type' => LotterySourceType::ORDER,
            'source_id' => 'playable-purchase', 'is_done' => false,
        ]);
        $otherUserIds = User::factory()->count(12)->create(['project_id' => $member->project_id])->modelKeys();
        $directory = sys_get_temp_dir().'/lottery-play-lock-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        DB::disconnect();
        $workers = [];
        try {
            foreach ([0, 1] as $index) {
                $pid = pcntl_fork();
                $this->assertNotSame(-1, $pid);
                if ($pid === 0) {
                    try {
                        DB::purge();
                        DB::statement('SET SESSION innodb_lock_wait_timeout = 5');
                        $firstRead = true;
                        DB::listen(function ($query) use ($directory, $index, &$firstRead): void {
                            $table = $index === 0 ? 'member_lottery_entitlements' : 'members';
                            if (! $firstRead || ! str_contains($query->sql, 'from `'.$table.'`') || ! str_contains($query->sql, 'for update')) {
                                return;
                            }
                            $firstRead = false;
                            file_put_contents($directory.'/read-'.$index, 'ready');
                            if ($index === 0) {
                                $this->waitUntil(fn () => is_file($directory.'/release'));
                            }
                        });
                        file_put_contents($directory.'/connection-'.$index, (string) DB::selectOne('SELECT CONNECTION_ID() AS id')->id);
                        if ($index === 0) {
                            $result = app(LotteryPlayService::class)->play($member, (int) $entitlement->id);
                            if (! $result['is_win']) {
                                throw new \RuntimeException('The only configured prize must win.');
                            }
                        } else {
                            DB::transaction(function () use ($member, $otherUserIds): void {
                                // Make the purchase heavier so reversed lock order deterministically aborts the play.
                                DB::table('users')->whereIn('id', $otherUserIds)->update(['name' => 'Concurrent purchase context']);
                                app(LotteryEntitlementService::class)->grantForPurchase(
                                    $member, 1000, LotterySourceType::SMAREGI_TRANSACTION, 'purchase-during-play',
                                );
                            });
                        }
                        file_put_contents($directory.'/result-'.$index, 'ok');
                        exit(0);
                    } catch (\Throwable $e) {
                        file_put_contents($directory.'/result-'.$index, get_class($e).': '.$e->getMessage());
                        exit(1);
                    }
                }
                $workers[] = $pid;
                if ($index === 0) {
                    $this->waitUntil(fn () => is_file($directory.'/read-0'));
                }
            }
            $this->waitUntil(fn () => is_file($directory.'/connection-1'));
            $connectionId = (int) file_get_contents($directory.'/connection-1');
            $this->waitUntil(function () use ($directory, $connectionId): bool {
                return is_file($directory.'/read-1') || DB::selectOne(
                    'SELECT COUNT(*) AS waiting FROM information_schema.innodb_trx WHERE trx_mysql_thread_id = ? AND trx_state = ?',
                    [$connectionId, 'LOCK WAIT'],
                )->waiting > 0;
            });
            file_put_contents($directory.'/release', 'release');
            foreach ($workers as $pid) {
                $status = 0;
                $this->waitUntil(fn () => pcntl_waitpid($pid, $status, WNOHANG) === $pid);
            }
            $workers = [];
            foreach ([0, 1] as $index) {
                $this->assertSame('ok', file_get_contents($directory.'/result-'.$index));
            }
            $this->assertTrue($entitlement->fresh()->is_done);
            $this->assertNotNull($entitlement->fresh()->member_coupon_id);
            $this->assertSame(1, $member->memberCoupons()->where('coupon_id', $coupon->id)->count());
            $this->assertSame(1, MemberLotteryEntitlement::where('member_id', $member->id)->count());
        } finally {
            file_put_contents($directory.'/release', 'release');
            foreach ($workers as $pid) {
                posix_kill($pid, SIGKILL);
                pcntl_waitpid($pid, $status);
            }
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
            DB::purge();
            $this->truncateDatabaseTables();
        }
    }

    public function test_all_game_entitlements_roll_back_when_one_creation_fails(): void
    {
        $member = $this->memberWithSettings(LotteryGrantTrigger::EVERY_PURCHASE);
        ProjectLotterySetting::where('project_id', $member->project_id)->update(['scratch_enabled' => true]);
        $active = true;
        MemberLotteryEntitlement::creating(function (MemberLotteryEntitlement $entitlement) use (&$active): void {
            if ($active && $entitlement->reward_type === LotteryRewardType::SCRATCH) {
                throw new \RuntimeException('Simulated entitlement persistence failure');
            }
        });
        try {
            try {
                app(LotteryEntitlementService::class)->grantForPurchase(
                    $member, 1000, LotterySourceType::ORDER, 'atomic-purchase',
                );
                $this->fail('The simulated persistence error must propagate.');
            } catch (\RuntimeException $e) {
                $this->assertSame('Simulated entitlement persistence failure', $e->getMessage());
            }
            $this->assertSame(0, MemberLotteryEntitlement::where('member_id', $member->id)->count());
        } finally {
            $active = false;
        }
    }

    private function memberWithSettings(string $trigger): Member
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ProjectLotterySetting::create([
            'project_id' => $project->id, 'gacha_enabled' => true,
            'scratch_enabled' => false, 'roulette_enabled' => false,
            'grant_trigger' => $trigger,
        ]);

        return Member::factory()->create(['project_id' => $project->id]);
    }

    private function waitUntil(callable $ready): void
    {
        $deadline = microtime(true) + 5;
        while (! $ready()) {
            if (microtime(true) >= $deadline) {
                throw new \RuntimeException('Timed out waiting for lottery worker.');
            }
            usleep(10000);
        }
    }
}
