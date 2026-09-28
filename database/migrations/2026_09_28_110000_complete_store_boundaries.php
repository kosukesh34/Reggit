<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Also upgrades workspaces that applied the initial store schema while it was being developed.
        foreach (['smaregi_store_id', 'smaregi_terminal_id'] as $column) {
            if (! Schema::hasColumn('stores', $column)) {
                Schema::table('stores', fn (Blueprint $table) => $table->string($column, 80)->nullable());
            }
        }
        if (! Schema::hasIndex('stores', 'stores_project_id_smaregi_store_id_unique')) {
            Schema::table('stores', fn (Blueprint $table) => $table->unique(['project_id', 'smaregi_store_id']));
        }
        if (! Schema::hasColumn('member_stores', 'memo')) {
            Schema::table('member_stores', fn (Blueprint $table) => $table->text('memo')->nullable());
        }
        DB::table('member_stores')->join('members', 'members.id', '=', 'member_stores.member_id')->whereNull('member_stores.memo')->update(['member_stores.memo' => DB::raw('members.memo')]);
        if (! Schema::hasColumn('announcements', 'private_image_path')) {
            Schema::table('announcements', fn (Blueprint $table) => $table->string('private_image_path')->nullable());
        }
        foreach (['point_transactions', 'member_stamp_transactions'] as $name) {
            if (! Schema::hasColumn($name, 'origin')) {
                Schema::table($name, fn (Blueprint $table) => $table->string('origin', 20)->default('store'));
            }
        }
        if (Schema::hasIndex('queue_entries', 'queue_entries_project_queue_number_unique')) {
            Schema::table('queue_entries', fn (Blueprint $table) => $table->dropUnique('queue_entries_project_queue_number_unique'));
        }
        if (! Schema::hasIndex('queue_entries', 'queue_entries_store_queue_number_unique')) {
            Schema::table('queue_entries', fn (Blueprint $table) => $table->unique(['project_id', 'store_id', 'queue_number'], 'queue_entries_store_queue_number_unique'));
        }
        if (Schema::hasIndex('store_app_credentials', 'store_app_credentials_project_id_app_unique')) {
            Schema::table('store_app_credentials', fn (Blueprint $table) => $table->dropUnique(['project_id', 'app']));
        }
        if (! Schema::hasIndex('store_app_credentials', 'store_app_credentials_project_id_store_id_app_unique')) {
            Schema::table('store_app_credentials', fn (Blueprint $table) => $table->unique(['project_id', 'store_id', 'app']));
        }
        if (! Schema::hasIndex('members', 'members_project_id_id_unique')) {
            Schema::table('members', fn (Blueprint $table) => $table->unique(['project_id', 'id']));
        }
        $foreignNames = array_column(Schema::getForeignKeys('member_stores'), 'name');
        if (! in_array('member_stores_project_id_member_id_foreign', $foreignNames, true)) {
            Schema::table('member_stores', fn (Blueprint $table) => $table->foreign(['project_id', 'member_id'])->references(['project_id', 'id'])->on('members')->cascadeOnDelete());
        }
        DB::table('stores')->where('store_key', 'main')->orderBy('id')->chunkById(100, function ($stores) {
            foreach ($stores as $store) {
                DB::table('announcements')->where('project_id', $store->project_id)->whereNull('store_ids')->whereNull('created_by_admin_id')->update(['store_ids' => json_encode([(int) $store->id])]);
            }
        });
    }

    public function down(): void
    {
        // Data is shared across stores after activation. Retain additive columns on rollback;
        // the base migration owns their removal when the entire feature is rolled back.
    }
};
