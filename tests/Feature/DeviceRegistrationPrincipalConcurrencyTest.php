<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\DeviceService;
use App\Util\AdminContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use Tests\TestCase;

class DeviceRegistrationPrincipalConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    #[BeforeClass]
    public static function resetMigrationBeforeClass(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    #[AfterClass]
    public static function resetMigrationAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    public function test_user_revocation_waits_until_device_registration_commits(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $primary = DB::getDefaultConnection();
        config(['database.connections.review_writer' => config('database.connections.'.$primary)]);
        $writer = DB::connection('review_writer');
        $writer->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $attempted = false;
        $blocked = false;
        Event::listen('eloquent.creating: '.UserDevice::class, function () use ($user, $writer, &$attempted, &$blocked): void {
            $attempted = true;
            try {
                $writer->table('users')->where('id', $user->id)->increment('authentication_version');
            } catch (QueryException $exception) {
                $this->assertSame(1205, (int) $exception->errorInfo[1]);
                $blocked = true;
            }
        });
        try {
            $device = app(DeviceService::class)->register($user, ['platform' => 'ios', 'device_identifier' => 'locked-user-device']);
            $this->assertTrue($attempted);
            $this->assertSame(0, (int) $user->fresh()->authentication_version);
            $this->assertTrue($blocked);
            $this->assertTrue($device->exists);
            $this->assertSame(1, $writer->table('users')->where('id', $user->id)->increment('authentication_version'));
            $this->assertSame(1, (int) $user->fresh()->authentication_version);
        } finally {
            DB::purge('review_writer');
        }
    }
}
