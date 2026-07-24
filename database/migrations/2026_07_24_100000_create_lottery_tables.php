<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('project_feature_flags', 'feature_gacha_enabled')) {
            Schema::table('project_feature_flags', function (Blueprint $table) {
                $table->boolean('feature_gacha_enabled')->default(false)->after('feature_gallery_enabled');
                $table->boolean('feature_scratch_enabled')->default(false)->after('feature_gacha_enabled');
            });
        }

        if (! Schema::hasTable('project_lottery_settings')) {
            Schema::create('project_lottery_settings', function (Blueprint $table) {
                $table->foreignId('project_id')->primary()->constrained('projects')->cascadeOnDelete();
                $table->boolean('gacha_enabled')->default(false);
                $table->boolean('scratch_enabled')->default(false);
                $table->string('grant_trigger', 32)->default('every_purchase');
                $table->decimal('min_amount', 12, 2)->nullable();
                $table->unsignedInteger('entitlement_expires_hours')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lottery_prizes')) {
            Schema::create('lottery_prizes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('reward_type', 16);
                $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
                $table->boolean('is_lose')->default(false);
                $table->unsignedTinyInteger('probability');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['project_id', 'reward_type', 'is_active']);
            });
        }

        if (! Schema::hasTable('member_lottery_entitlements')) {
            Schema::create('member_lottery_entitlements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
                $table->string('reward_type', 16);
                $table->string('source_type', 32);
                $table->string('source_id', 64);
                $table->boolean('is_done')->default(false);
                $table->timestamp('done_at')->nullable();
                $table->foreignId('won_coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
                $table->foreignId('member_coupon_id')->nullable()->constrained('member_coupons')->nullOnDelete();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['project_id', 'source_type', 'source_id', 'reward_type'],
                    'member_lottery_entitlements_source_unique'
                );
                $table->index(['member_id', 'is_done', 'reward_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_lottery_entitlements');
        Schema::dropIfExists('lottery_prizes');
        Schema::dropIfExists('project_lottery_settings');

        if (Schema::hasColumn('project_feature_flags', 'feature_gacha_enabled')) {
            Schema::table('project_feature_flags', function (Blueprint $table) {
                $table->dropColumn(['feature_gacha_enabled', 'feature_scratch_enabled']);
            });
        }
    }
};
