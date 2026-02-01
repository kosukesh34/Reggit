<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_table_exists_before_member_coupons(): void
    {
        $this->assertTrue(Schema::hasTable('projects'));
        $this->assertTrue(Schema::hasTable('member_coupons'));
    }

    public function test_member_coupons_has_project_id_foreign_key(): void
    {
        $this->assertTrue(Schema::hasTable('member_coupons'));
        $this->assertTrue(Schema::hasColumn('member_coupons', 'project_id'));

        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'member_coupons' AND COLUMN_NAME = 'project_id'
            AND REFERENCED_TABLE_NAME = 'projects'
        ", [config('database.connections.mysql.database')]);

        $this->assertNotEmpty($foreignKeys, 'member_coupons.project_id should reference projects.id');
    }

    public function test_default_project_exists_after_migration(): void
    {
        $project = DB::table('projects')->where('id', 1)->first();
        $this->assertNotNull($project);
        $this->assertSame('default', $project->project_key);
    }
}
