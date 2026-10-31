<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\BusinessSettingStore;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\Reservation;
use App\Models\Store;
use App\Models\User;
use App\UseCases\Reservation\Data\ReservationStoreInput;
use App\UseCases\Reservation\ReservationStoreUseCase;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReservationCapacityConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    public function test_two_concurrent_requests_cannot_take_the_last_place(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires MySQL and pcntl for independent concurrent connections.');
        }

        $project = Project::factory()->create();
        $store = $project->stores()->firstOrFail();
        $users = User::factory()->count(2)->create(['project_id' => $project->id]);
        foreach ($users as $user) {
            Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
            $user->load('member');
        }
        ShopSettingStore::setValue('reservation_capacity_per_slot', 1, 'integer', $project->id);
        $slot = BusinessSettingStore::getReservationTimeSlots($project->id)[0];
        $payload = ['reserved_at' => now()->addDay()->format('Y-m-d').' '.$slot, 'number_of_people' => 1];
        $directory = sys_get_temp_dir().'/reservation-capacity-'.bin2hex(random_bytes(8));
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
                        app(StoreContext::class)->bind(Request::create('/', 'POST', ['store_id' => $store->id]), $project->id);
                        file_put_contents($directory.'/started-'.$index, 'ready');
                        DB::listen(function ($query) use ($directory, $index): void {
                            if (! str_contains($query->sql, 'from `reservations`') || ! str_contains($query->sql, 'for update')) {
                                return;
                            }
                            file_put_contents($directory.'/checked-'.$index, 'checked');
                            if ($index === 0) {
                                $this->waitForFile($directory.'/release');
                            }
                        });
                        $result = app(ReservationStoreUseCase::class)->handle(new ReservationStoreInput($user, $payload));
                        file_put_contents($directory.'/result-'.$index, $result->reservation !== null ? 'created' : $result->availabilityError);
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
            $this->assertFileDoesNotExist($directory.'/checked-1', 'Second capacity check must wait for the first reservation to commit.');
            file_put_contents($directory.'/release', 'release');
            foreach ($workers as $pid) {
                pcntl_waitpid($pid, $status);
                $this->assertSame(0, pcntl_wexitstatus($status));
            }
            $workers = [];
            $this->assertSame('created', file_get_contents($directory.'/result-0'));
            $this->assertSame('選択した時間帯は満席です', file_get_contents($directory.'/result-1'));
            $this->assertSame(1, Reservation::withoutGlobalScopes()->where('project_id', $project->id)->count());
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

    public function test_concurrent_requests_in_different_stores_receive_distinct_numbers(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires MySQL and pcntl for independent concurrent connections.');
        }

        $project = Project::factory()->create();
        $stores = [$project->stores()->firstOrFail(), Store::create(['project_id' => $project->id, 'store_key' => 'branch', 'name' => 'Branch', 'is_active' => true])];
        $users = User::factory()->count(2)->create(['project_id' => $project->id]);
        foreach ($users as $user) {
            Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
            $user->load('member');
        }
        ShopSettingStore::setValue('reservation_capacity_per_slot', 1, 'integer', $project->id);
        $slot = BusinessSettingStore::getReservationTimeSlots($project->id)[0];
        $payload = ['reserved_at' => now()->addDay()->format('Y-m-d').' '.$slot, 'number_of_people' => 1];
        $directory = sys_get_temp_dir().'/reservation-capacity-'.bin2hex(random_bytes(8));
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
                        app(StoreContext::class)->bind(Request::create('/', 'POST', ['store_id' => $stores[$index]->id]), $project->id);
                        file_put_contents($directory.'/started-'.$index, 'ready');
                        DB::listen(function ($query) use ($directory, $index): void {
                            if (! str_contains($query->sql, 'from `reservations`') || ! str_contains($query->sql, 'for update')) {
                                return;
                            }
                            file_put_contents($directory.'/checked-'.$index, 'checked');
                            if ($index === 0) {
                                $this->waitForFile($directory.'/release');
                            }
                        });
                        $result = app(ReservationStoreUseCase::class)->handle(new ReservationStoreInput($user, $payload));
                        file_put_contents($directory.'/result-'.$index, $result->reservation !== null ? 'created' : $result->availabilityError);
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
            $this->waitForFile($directory.'/checked-1');
            file_put_contents($directory.'/release', 'release');
            foreach ($workers as $pid) {
                pcntl_waitpid($pid, $status);
                $this->assertSame(0, pcntl_wexitstatus($status));
            }
            $workers = [];
            $this->assertSame('created', file_get_contents($directory.'/result-0'));
            $this->assertSame('created', file_get_contents($directory.'/result-1'));
            $numbers = Reservation::withoutGlobalScopes()->where('project_id', $project->id)->pluck('reservation_number')->all();
            $this->assertCount(2, $numbers);
            $this->assertCount(2, array_unique($numbers));
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

    public function test_direct_creation_holds_the_allocation_lock_through_insert(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        AdminContext::setCurrentProject($project);
        $connection = config('database.connections.mysql');
        $pdo = new \PDO('mysql:host='.$connection['host'].';port='.$connection['port'].';dbname='.$connection['database'], $connection['username'], $connection['password']);
        Event::listen('eloquent.creating: '.Reservation::class, function () use ($pdo, $project): void {
            $this->assertGreaterThan(0, DB::transactionLevel());
            try {
                $statement = $pdo->prepare('SELECT id FROM projects WHERE id = ? FOR UPDATE NOWAIT');
                $statement->execute([$project->id]);
                $this->fail('Project allocation lock must remain held until insert');
            } catch (\PDOException $exception) {
                $this->assertSame(3572, $exception->errorInfo[1]);
            }
        });
        try {
            $reservation = Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
            $this->assertDatabaseHas('reservations', ['id' => $reservation->id]);
            $statement = $pdo->prepare('SELECT id FROM projects WHERE id = ? FOR UPDATE NOWAIT');
            $statement->execute([$project->id]);
            $this->assertEquals($project->id, $statement->fetchColumn());
        } finally {
            $this->truncateDatabaseTables();
            RefreshDatabaseState::$migrated = false;
        }
    }

    private function waitForFile(string $path): void
    {
        $deadline = microtime(true) + 5;
        while (! is_file($path)) {
            if (microtime(true) >= $deadline) {
                throw new \RuntimeException('Timed out waiting for reservation worker.');
            }
            usleep(10000);
        }
    }
}
