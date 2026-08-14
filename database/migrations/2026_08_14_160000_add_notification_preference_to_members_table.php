<?php

use App\Enums\MemberNotificationPreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->string('notification_preference', 24)
                ->default(MemberNotificationPreference::ALL)
                ->after('mail_receive_flag');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn('notification_preference');
        });
    }
};
