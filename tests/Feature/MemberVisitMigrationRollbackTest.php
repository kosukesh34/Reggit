<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberVisitMigrationRollbackTest extends TestCase
{
    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();
        $filename = '2026_07_19_021500_add_amount_and_smaregi_transaction_to_member_visits_table.php';
        $paths = array_values(array_filter(glob(database_path('migrations/*.php')), fn ($path) => basename($path) < $filename));
        $this->artisan('migrate:fresh', ['--path' => $paths, '--realpath' => true])->assertExitCode(0);
        $this->migration = require database_path('migrations/'.$filename);
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_rollback_preserves_project_foreign_key_and_supports_reapplication(): void
    {
        $foreignKeys = Schema::getForeignKeys('member_visits');
        $this->migration->up();
        // MySQL may replace the implicit foreign-key index with this composite index.
        foreach (Schema::getIndexes('member_visits') as $index) {
            if ($index['columns'] === ['project_id']) {
                Schema::table('member_visits', fn ($table) => $table->dropIndex($index['name']));
            }
        }
        $this->assertFalse(Schema::hasIndex('member_visits', ['project_id']));
        $this->migration->down();
        $this->assertSame($foreignKeys, Schema::getForeignKeys('member_visits'));
        $this->assertTrue(Schema::hasIndex('member_visits', ['project_id']));
        $this->assertFalse(Schema::hasColumn('member_visits', 'amount'));
        $this->assertFalse(Schema::hasColumn('member_visits', 'smaregi_transaction_head_id'));
        $this->assertFalse(Schema::hasIndex('member_visits', 'member_visits_project_smaregi_tx_unique'));
        $this->migration->up();
        $this->migration->down();
        $this->assertSame($foreignKeys, Schema::getForeignKeys('member_visits'));
        $this->assertSame(1, DB::table('projects')->where('id', 1)->count());
    }
}
