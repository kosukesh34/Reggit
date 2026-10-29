<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // LINE の unfollow（ブロック）を受けた日時。follow で解除する
            $table->timestamp('line_blocked_at')->nullable();
        });
        Schema::create('line_delivery_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('announcement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 20);
            $table->string('error_message', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_delivery_failures');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('line_blocked_at'));
    }
};
