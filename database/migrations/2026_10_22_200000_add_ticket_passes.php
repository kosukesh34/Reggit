<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('ticket_uses')->nullable()->after('stock')->comment('回数券の利用回数。NULLは通常商品');
            $table->unsignedSmallInteger('ticket_valid_days')->nullable()->after('ticket_uses')->comment('回数券の有効日数。NULLは無期限');
        });

        Schema::table('project_feature_flags', function (Blueprint $table): void {
            $table->boolean('feature_tickets_enabled')->default(false)->after('feature_shop_enabled');
        });

        Schema::create('member_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('total_uses');
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'member_id']);
        });

        Schema::create('member_ticket_uses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('used_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['member_ticket_id', 'cancelled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_ticket_uses');
        Schema::dropIfExists('member_tickets');

        Schema::table('project_feature_flags', function (Blueprint $table): void {
            $table->dropColumn('feature_tickets_enabled');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['ticket_uses', 'ticket_valid_days']);
        });
    }
};
