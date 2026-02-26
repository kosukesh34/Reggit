<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smaregi_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_id')->unique()->comment('スマレジから通知された契約ID');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('client_id')->nullable();
            $table->json('plan')->nullable()->comment('プラン情報（name, price, trial_days等）');
            $table->json('options')->nullable()->comment('オプション一覧');
            $table->string('last_action', 32)->nullable()->comment('start,end,change-plan,change-options,force-stop,cancel-force-stop');
            $table->string('status', 16)->default('active')->comment('active, inactive');
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smaregi_contracts');
    }
};
