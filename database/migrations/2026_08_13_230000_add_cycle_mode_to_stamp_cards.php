<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->string('cycle_mode', 16)->default('repeat')->after('is_active');
            $table->foreignId('next_stamp_card_id')->nullable()->after('cycle_mode')
                ->constrained('stamp_cards')->nullOnDelete();
        });

        Schema::table('member_stamps', function (Blueprint $table) {
            $table->unsignedInteger('cycle_count')->default(0)->after('current_stamps');
        });
    }

    public function down(): void
    {
        Schema::table('stamp_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('next_stamp_card_id');
            $table->dropColumn('cycle_mode');
        });

        Schema::table('member_stamps', function (Blueprint $table) {
            $table->dropColumn('cycle_count');
        });
    }
};
