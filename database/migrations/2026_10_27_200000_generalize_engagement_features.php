<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_popups', function (Blueprint $table): void {
            $table->string('audience', 20)->default('all')->after('frequency')->comment('all|members|guests|segment');
            $table->json('audience_tag_ids')->nullable()->after('audience')->comment('segment のとき、いずれかのタグが付いた会員');
            $table->json('audience_ranks')->nullable()->after('audience_tag_ids')->comment('segment のとき、いずれかのランクの会員');
            $table->json('store_ids')->nullable()->after('audience_ranks')->comment('表示する店舗。NULLは全店舗');
            $table->string('secondary_button_label', 40)->nullable()->after('link_url');
            $table->string('secondary_link_url', 500)->nullable()->after('secondary_button_label');
        });

        Schema::table('surveys', function (Blueprint $table): void {
            $table->string('invite_trigger', 20)->nullable()->after('reward_points')->comment('visit|order|reservation|registration。NULLは自動で依頼しない');
            $table->renameColumn('send_after_visit_days', 'invite_delay_days');
        });
        DB::table('surveys')->whereNotNull('invite_delay_days')->update(['invite_trigger' => 'visit']);
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table): void {
            $table->renameColumn('invite_delay_days', 'send_after_visit_days');
            $table->dropColumn('invite_trigger');
        });
        Schema::table('promo_popups', function (Blueprint $table): void {
            $table->dropColumn(['audience', 'audience_tag_ids', 'audience_ranks', 'store_ids', 'secondary_button_label', 'secondary_link_url']);
        });
    }
};
