<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('member_id')->constrained()->nullOnDelete();
            $table->string('qr_token', 64)->nullable()->after('queue_number');
            $table->unique('qr_token', 'queue_entries_qr_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->dropUnique('queue_entries_qr_token_unique');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('qr_token');
        });
    }
};
