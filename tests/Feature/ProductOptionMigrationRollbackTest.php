<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductOptionMigrationRollbackTest extends TestCase
{
    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();
        $filename = '2026_09_24_020000_add_smaregi_ids_to_product_options.php';
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
        $foreignKeys = Schema::getForeignKeys('product_option_groups');
        $this->migration->up();
        // MySQL may replace the implicit foreign-key index with this composite index.
        foreach (Schema::getIndexes('product_option_groups') as $index) {
            if ($index['columns'] === ['project_id']) {
                Schema::table('product_option_groups', fn ($table) => $table->dropIndex($index['name']));
            }
        }
        $this->assertFalse(Schema::hasIndex('product_option_groups', ['project_id']));
        $this->migration->down();
        $this->assertSame($foreignKeys, Schema::getForeignKeys('product_option_groups'));
        $this->assertTrue(Schema::hasIndex('product_option_groups', ['project_id']));
        $this->assertFalse(Schema::hasColumn('product_option_groups', 'smaregi_option_group_id'));
        $this->assertFalse(Schema::hasColumn('product_options', 'smaregi_product_id'));
        $this->migration->up();
        $this->migration->down();
        $this->assertSame($foreignKeys, Schema::getForeignKeys('product_option_groups'));
        $this->assertSame(1, DB::table('projects')->where('id', 1)->count());
    }
}
