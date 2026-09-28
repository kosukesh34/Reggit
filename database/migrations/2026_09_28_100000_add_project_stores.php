<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $transactions = ['orders', 'reservations', 'queue_entries', 'member_visits', 'point_transactions', 'member_stamp_transactions', 'coupon_redemptions', 'store_app_credentials'];

    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('store_key', 80);
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('smaregi_store_id', 80)->nullable();
            $table->string('smaregi_terminal_id', 80)->nullable();
            $table->unique(['project_id', 'smaregi_store_id']);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'store_key']);
            $table->unique(['project_id', 'id']);
        });
        Schema::table('members', fn (Blueprint $table) => $table->unique(['project_id', 'id']));
        foreach (['admin_user_stores' => ['admin_user_id', 'admin_users'], 'member_stores' => ['member_id', 'members']] as $name => [$key, $parent]) {
            Schema::create($name, function (Blueprint $table) use ($key, $parent) {
                $table->id();
                $table->foreignId('project_id')->constrained()->restrictOnDelete();
                $table->foreignId($key)->constrained($parent)->cascadeOnDelete();
                if ($key === 'member_id') {
                    $table->foreign(['project_id', 'member_id'])->references(['project_id', 'id'])->on('members')->cascadeOnDelete();
                }
                $table->unsignedBigInteger('store_id');
                $table->foreign(['project_id', 'store_id'])->references(['project_id', 'id'])->on('stores')->restrictOnDelete();
                if ($key === 'member_id') {
                    $table->text('memo')->nullable();
                }
                $table->timestamps();
                $table->unique([$key, 'store_id']);
                $table->index(['project_id', 'store_id', $key]);
            });
        }
        foreach ($this->transactions as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('store_id')->nullable();
                $table->foreign(['project_id', 'store_id'])->references(['project_id', 'id'])->on('stores')->restrictOnDelete();
                $table->index(['project_id', 'store_id']);
            });
        }
        foreach (['point_transactions', 'member_stamp_transactions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->string('origin', 20)->default('store'));
        }
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->dropUnique('queue_entries_project_queue_number_unique');
            $table->unique(['project_id', 'store_id', 'queue_number'], 'queue_entries_store_queue_number_unique');
        });
        Schema::table('store_app_credentials', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'app']);
            $table->unique(['project_id', 'store_id', 'app']);
        });
        Schema::table('announcements', function (Blueprint $table) {
            $table->json('store_ids')->nullable();
            $table->string('private_image_path')->nullable();
            $table->string('visibility', 20)->default('public');
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
        });
        DB::table('projects')->orderBy('id')->chunkById(100, function ($projects) {
            foreach ($projects as $project) {
                $storeId = DB::table('stores')->insertGetId(['project_id' => $project->id, 'store_key' => 'main', 'name' => $project->name, 'is_active' => $project->is_active, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('announcements')->where('project_id', $project->id)->update(['store_ids' => json_encode([$storeId])]);
                foreach ($this->transactions as $name) {
                    DB::table($name)->where('project_id', $project->id)->update(['store_id' => $storeId]);
                }
                DB::table('members')->where('project_id', $project->id)->orderBy('id')->chunkById(500, function ($members) use ($project, $storeId) {
                    DB::table('member_stores')->insert($members->map(fn ($member) => ['project_id' => $project->id, 'store_id' => $storeId, 'member_id' => $member->id, 'memo' => $member->memo ?? null, 'created_at' => now(), 'updated_at' => now()])->all());
                });
                $adminIds = DB::table('admin_user_projects')->where('project_id', $project->id)->pluck('admin_user_id')
                    ->merge(DB::table('admin_users')->where('project_id', $project->id)->pluck('id'))->unique();
                foreach ($adminIds as $adminId) {
                    DB::table('admin_user_stores')->insert(['project_id' => $project->id, 'store_id' => $storeId, 'admin_user_id' => $adminId, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        });
        DB::table('announcements')->whereNotNull('target_member_ids')->update(['visibility' => 'recipients']);
    }

    public function down(): void
    {
        Schema::table('queue_entries', fn (Blueprint $table) => $table->dropUnique('queue_entries_store_queue_number_unique'));
        foreach (['point_transactions', 'member_stamp_transactions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('origin'));
        }
        Schema::table('store_app_credentials', fn (Blueprint $table) => $table->dropUnique(['project_id', 'store_id', 'app']));
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_admin_id');
            $table->dropColumn(['store_ids', 'visibility', 'private_image_path']);
        });
        foreach ($this->transactions as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropForeign(['project_id', 'store_id']);
                $table->dropIndex(['project_id', 'store_id']);
                $table->dropColumn('store_id');
            });
        }
        Schema::dropIfExists('member_stores');
        Schema::dropIfExists('admin_user_stores');
        Schema::table('members', fn (Blueprint $table) => $table->dropUnique(['project_id', 'id']));
        Schema::dropIfExists('stores');
    }
};
