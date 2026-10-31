<?php

namespace Tests\Feature;

use App\Enums\ScheduledDeliveryType;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AfterRegistrationDelivery;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\Member\MemberRegistrar;
use App\Services\ScheduledDeliverySendService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberRegistrationConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    public function test_concurrent_users_receive_distinct_sequential_member_numbers(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires MySQL and pcntl for independent concurrent connections.');
        }

        $project = Project::factory()->create();
        config(['smaregi.contract_id' => '']);
        app(ShopSettingStore::class)->setValue('member_code_type', 'sequential', 'string', $project->id);
        app(ShopSettingStore::class)->setValue('member_code_length', 4, 'integer', $project->id);
        $users = User::factory()->count(2)->create(['project_id' => $project->id]);
        $directory = sys_get_temp_dir().'/member-registration-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        DB::disconnect();
        $workers = [];

        try {
            foreach ($users as $index => $user) {
                $pid = pcntl_fork();
                $this->assertNotSame(-1, $pid);
                if ($pid === 0) {
                    try {
                        DB::purge();
                        AdminContext::setCurrentProject($project);
                        file_put_contents($directory.'/started-'.$index, 'ready');
                        DB::listen(function ($query) use ($directory, $index): void {
                            if (! str_contains($query->sql, 'from `projects`') || ! str_contains($query->sql, 'for update')) {
                                return;
                            }
                            file_put_contents($directory.'/checked-'.$index, 'checked');
                            if ($index === 0) {
                                $this->waitForFile($directory.'/release');
                            }
                        });
                        $result = app(MemberRegistrar::class)->registerMember($user, ['name' => 'Registered']);
                        file_put_contents($directory.'/result-'.$index, $result->member_number);
                        exit(0);
                    } catch (\Throwable $e) {
                        file_put_contents($directory.'/result-'.$index, get_class($e).': '.$e->getMessage());
                        exit(1);
                    }
                }
                $workers[] = $pid;
                if ($index === 0) {
                    $this->waitForFile($directory.'/checked-0');
                }
            }
            $this->waitForFile($directory.'/started-1');
            usleep(200000);
            $this->assertFileDoesNotExist($directory.'/checked-1', 'Second registration must wait for the first allocation to commit.');
            file_put_contents($directory.'/release', 'release');
            foreach ($workers as $pid) {
                pcntl_waitpid($pid, $status);
                $this->assertSame(0, pcntl_wexitstatus($status));
            }
            $workers = [];
            $this->assertSame('0001', file_get_contents($directory.'/result-0'));
            $this->assertSame('0002', file_get_contents($directory.'/result-1'));
            $this->assertSame(2, Member::withoutGlobalScopes()->where('project_id', $project->id)->count());
        } finally {
            file_put_contents($directory.'/release', 'release');
            foreach ($workers as $pid) {
                pcntl_waitpid($pid, $status);
            }
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
            $this->truncateDatabaseTables();
            RefreshDatabaseState::$migrated = false;
        }
    }

    public function test_registration_delivery_observes_committed_member_and_profile(): void
    {
        config(['smaregi.contract_id' => '']);
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id, 'name' => 'Before']);
        $config = AfterRegistrationDelivery::create([
            'project_id' => $project->id, 'schedule_type' => ScheduledDeliveryType::NEW_USER_MINUTES,
            'delay_minutes' => 0, 'title' => 'Welcome', 'is_active' => true,
        ]);
        $sender = \Mockery::mock(ScheduledDeliverySendService::class);
        $sender->shouldReceive('sendToMember')->once()->withArgs(function ($delivery, $member) use ($config, $user): bool {
            $this->assertSame(0, DB::transactionLevel());
            $this->assertSame('Registered', $user->fresh()->name);
            $this->assertDatabaseHas('members', ['id' => $member->id, 'user_id' => $user->id]);

            return $delivery->id === $config->id;
        })->andReturn(true);
        $this->app->instance(ScheduledDeliverySendService::class, $sender);
        app(MemberRegistrar::class)->registerMember($user, ['name' => 'Registered']);
    }

    private function waitForFile(string $path): void
    {
        $deadline = microtime(true) + 5;
        while (! is_file($path)) {
            if (microtime(true) >= $deadline) {
                throw new \RuntimeException('Timed out waiting for registration worker.');
            }
            usleep(10000);
        }
    }
}
