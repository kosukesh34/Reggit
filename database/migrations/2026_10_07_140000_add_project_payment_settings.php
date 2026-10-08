<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('account_id');
            $table->string('publishable_key');
            $table->text('secret_key');
            $table->text('webhook_secret');
            $table->json('methods');
            $table->timestamps();
            $table->index(['project_id', 'id']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('payment_setting_id')->nullable()->constrained('project_payment_settings')->restrictOnDelete();
            $table->string('payment_brand')->nullable();
            $table->string('paid_payment_brand')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_setting_id');
            $table->dropColumn(['payment_brand', 'paid_payment_brand']);
        });
        Schema::dropIfExists('project_payment_settings');
    }
};
