<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_user_permissions')) {
            return;
        }

        Schema::create('admin_user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->string('permission', 50)->comment('権限キー（例: orders.view, products.manage）');
            $table->timestamps();

            $table->unique(['admin_user_id', 'permission'], 'aup_user_perm_unique');
            $table->index('permission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_user_permissions');
    }
};
