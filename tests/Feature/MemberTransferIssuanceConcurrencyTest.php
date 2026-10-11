<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberTransferCode;
use App\Models\Project;
use App\Models\User;
use App\UseCases\Member\IssueMemberTransferCodeUseCase;
use App\Util\AdminContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use Tests\TestCase;

class MemberTransferIssuanceConcurrencyTest extends TestCase
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

    public function test_overlapping_issuance_is_serialized_and_leaves_one_active_code(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $primary = DB::getDefaultConnection();
        config(['database.connections.review_writer' => config('database.connections.'.$primary)]);
        DB::connection('review_writer')->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $attempted = false;
        $blocked = false;
        Event::listen('eloquent.creating: '.MemberTransferCode::class, function () use ($user, $primary, &$attempted, &$blocked): void {
            if ($attempted) {
                return;
            }
            $attempted = true;
            DB::setDefaultConnection('review_writer');
            try {
                app(IssueMemberTransferCodeUseCase::class)->handle($user, null);
            } catch (QueryException $exception) {
                $this->assertSame(1205, (int) $exception->errorInfo[1]);
                $blocked = true;
            } finally {
                DB::setDefaultConnection($primary);
            }
        });
        try {
            $first = app(IssueMemberTransferCodeUseCase::class)->handle($user, null);
            $this->assertTrue($attempted);
            $this->assertSame(1, MemberTransferCode::where('user_id', $user->id)->whereNull('consumed_at')->count());
            $this->assertTrue($blocked);
            DB::setDefaultConnection('review_writer');
            $second = app(IssueMemberTransferCodeUseCase::class)->handle($user, null);
            DB::setDefaultConnection($primary);
            $this->assertSame(1, MemberTransferCode::where('user_id', $user->id)->whereNull('consumed_at')->count());
            $this->assertNotNull(MemberTransferCode::where('code_hash', hash('sha256', $first->code))->firstOrFail()->consumed_at);
            $this->assertNull(MemberTransferCode::where('code_hash', hash('sha256', $second->code))->firstOrFail()->consumed_at);
        } finally {
            DB::setDefaultConnection($primary);
            DB::purge('review_writer');
        }
    }
}
