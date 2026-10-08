<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectStoreRollbackTest extends TestCase
{
    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();
        $filename = '2026_09_28_100000_add_project_stores.php';
        $paths = array_values(array_filter(glob(database_path('migrations/*.php')), fn ($path) => basename($path) < $filename));
        $this->artisan('migrate:fresh', ['--path' => $paths, '--realpath' => true])->assertExitCode(0);
        $this->migration = require database_path('migrations/'.$filename);
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_up_down_preserves_legacy_data_and_restores_project_unique_constraints(): void
    {
        $tables = ['orders', 'reservations', 'queue_entries', 'member_visits', 'point_transactions', 'member_stamp_transactions', 'coupon_redemptions', 'store_app_credentials', 'members'];
        $foreignKeys = [];
        foreach ($tables as $table) {
            $foreignKeys[$table] = Schema::getForeignKeys($table);
        }
        $queueId = DB::table('queue_entries')->insertGetId(['project_id' => 1, 'queue_number' => 7, 'guest_name' => 'Legacy']);
        DB::table('queue_entries')->insert([
            ['project_id' => 1, 'queue_number' => null],
            ['project_id' => 1, 'queue_number' => null],
        ]);
        $otherProject = DB::table('projects')->insertGetId(['project_key' => 'other', 'name' => 'Other']);
        DB::table('queue_entries')->insert(['project_id' => $otherProject, 'queue_number' => 7]);
        $credentialId = $this->credential();
        $this->migration->up();
        $storeId = DB::table('stores')->where('project_id', 1)->value('id');
        $this->assertDatabaseHas('queue_entries', ['id' => $queueId, 'store_id' => $storeId]);
        $this->assertDatabaseHas('store_app_credentials', ['id' => $credentialId, 'store_id' => $storeId]);
        $this->migration->down();
        $this->assertFalse(Schema::hasTable('stores'));
        $this->assertFalse(Schema::hasColumn('queue_entries', 'store_id'));
        $this->assertTrue(Schema::hasIndex('queue_entries', 'queue_entries_project_queue_number_unique', 'unique'));
        $this->assertTrue(Schema::hasIndex('store_app_credentials', 'store_app_credentials_project_id_app_unique', 'unique'));
        $this->assertDatabaseHas('queue_entries', ['id' => $queueId, 'project_id' => 1, 'queue_number' => 7, 'guest_name' => 'Legacy']);
        $this->assertDatabaseHas('store_app_credentials', ['id' => $credentialId, 'project_id' => 1, 'app' => 'kitchen']);
        $this->assertSame(2, DB::table('queue_entries')->whereNull('queue_number')->count());
        $this->assertSame(2, DB::table('queue_entries')->where('queue_number', 7)->count());
        foreach ($tables as $table) {
            $this->assertSame($foreignKeys[$table], Schema::getForeignKeys($table));
            $this->assertTrue(collect(Schema::getIndexes($table))->contains(fn (array $index) => ($index['columns'][0] ?? null) === 'project_id'));
            $this->assertFalse(Schema::hasColumn($table, 'store_id'));
        }
        $this->assertFalse(Schema::hasIndex('members', ['project_id', 'id'], 'unique'));
        $this->assertFalse(Schema::hasTable('member_stores'));
        $this->assertFalse(Schema::hasTable('admin_user_stores'));
        $this->migration->up();
        $this->assertDatabaseHas('queue_entries', ['id' => $queueId, 'project_id' => 1, 'queue_number' => 7]);
    }

    public function test_queue_number_conflicts_stop_rollback_before_any_schema_or_data_changes(): void
    {
        $this->migration->up();
        [$a, $b] = $this->stores();
        DB::table('queue_entries')->insert([
            ['project_id' => 1, 'store_id' => $a, 'queue_number' => 7],
            ['project_id' => 1, 'store_id' => $b, 'queue_number' => 7],
        ]);
        $this->assertRollbackBlocked('queue_entries');
    }

    public function test_credential_conflicts_stop_rollback_before_any_schema_or_data_changes(): void
    {
        $this->migration->up();
        [$a, $b] = $this->stores();
        $this->credential($a);
        $this->credential($b);
        $this->assertRollbackBlocked('store_app_credentials');
    }

    private function stores(): array
    {
        $a = DB::table('stores')->where('project_id', 1)->value('id');
        $b = DB::table('stores')->insertGetId(['project_id' => 1, 'store_key' => 'branch', 'name' => 'Branch']);

        return [$a, $b];
    }

    private function assertRollbackBlocked(string $table): void
    {
        $before = $this->schemaAndData();
        try {
            $this->migration->down();
            $this->fail('Rollback should reject legacy uniqueness conflicts');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString($table.' has duplicate', $exception->getMessage());
        }
        $this->assertSame($before, $this->schemaAndData());
    }

    private function schemaAndData(): array
    {
        $snapshot = [];
        foreach (Schema::getTableListing() as $table) {
            $snapshot[$table] = [
                Schema::getColumns($table), Schema::getIndexes($table), Schema::getForeignKeys($table),
                DB::table($table)->get()->map(fn ($row) => (array) $row)->all(),
            ];
        }

        return $snapshot;
    }

    private function credential(?int $storeId = null): int
    {
        $adminId = DB::table('admin_users')->insertGetId([
            'project_id' => 1, 'login_id' => uniqid('rollback-'), 'email' => uniqid().'@example.test',
            'name' => 'Rollback', 'password' => 'unused',
        ]);
        $attributes = ['project_id' => 1, 'admin_user_id' => $adminId, 'app' => 'kitchen'];
        if ($storeId !== null) {
            $attributes['store_id'] = $storeId;
        }

        return DB::table('store_app_credentials')->insertGetId($attributes);
    }
}
