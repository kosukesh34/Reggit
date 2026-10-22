<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('subscription_enabled')->default(false)->after('ticket_valid_days')->comment('月額サブスクとして販売する');
            $table->unsignedInteger('subscription_monthly_uses')->nullable()->after('subscription_enabled')->comment('サブスクの月間利用回数。NULLは無制限');
            $table->unsignedSmallInteger('subscription_daily_limit')->nullable()->after('subscription_monthly_uses')->comment('サブスクの1日の利用上限。NULLは制限なし');
        });

        Schema::create('member_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('payment_setting_id');
            $table->string('stripe_subscription_id')->unique();
            $table->string('name');
            $table->string('status', 32);
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('current_period_end')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'member_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('stripe_invoice_id')->nullable()->unique()->after('payment_setting_id');
        });

        Schema::table('member_tickets', function (Blueprint $table): void {
            $table->unsignedInteger('total_uses')->nullable()->change();
            $table->unsignedSmallInteger('daily_limit')->nullable()->after('used_count');
            $table->foreignId('member_subscription_id')->nullable()->after('member_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('member_tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('member_subscription_id');
            $table->dropColumn('daily_limit');
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['stripe_invoice_id']);
            $table->dropColumn('stripe_invoice_id');
        });
        Schema::dropIfExists('member_subscriptions');
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['subscription_enabled', 'subscription_monthly_uses', 'subscription_daily_limit']);
        });
    }
};
