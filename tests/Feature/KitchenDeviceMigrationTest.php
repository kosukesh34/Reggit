<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Store;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KitchenDeviceMigrationTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_legacy_devices_are_only_assigned_when_the_project_has_one_store(): void
    {
        $migration = require database_path('migrations/2026_10_09_065909_add_store_id_to_kitchen_devices.php');
        $migration->down();
        $single = Project::factory()->create();
        $multiple = Project::factory()->create();
        Store::create(['project_id' => $multiple->id, 'store_key' => 'other', 'name' => 'Other', 'is_active' => true]);
        DB::table('kitchen_devices')->insert([
            ['project_id' => $single->id, 'push_token' => 'single-token'],
            ['project_id' => $multiple->id, 'push_token' => 'unknown-token'],
        ]);
        $migration->up();
        $this->assertDatabaseHas('kitchen_devices', [
            'project_id' => $single->id, 'push_token' => 'single-token',
            'store_id' => $single->stores()->firstOrFail()->id,
        ]);
        $this->assertDatabaseHas('kitchen_devices', [
            'project_id' => $multiple->id, 'push_token' => 'unknown-token', 'store_id' => null,
        ]);
    }
}
