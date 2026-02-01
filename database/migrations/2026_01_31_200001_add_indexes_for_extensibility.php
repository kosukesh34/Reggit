<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 一覧・フィルタ・日付範囲検索などで使用するカラムにインデックスを追加し、
 * データ増加時もパフォーマンスと拡張性を確保する。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('order_type');
            $table->index('created_at');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->index('status');
            $table->index('reserved_at');
        });

        Schema::table('queue_entries', function (Blueprint $table) {
            $table->index('status');
            $table->index('queue_number');
            $table->index('created_at');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->index('published_at');
            $table->index('is_pinned');
            $table->index('expires_at');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('end_at');
        });

        Schema::table('point_transactions', function (Blueprint $table) {
            $table->index(['member_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['order_type']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['reserved_at']);
        });

        Schema::table('queue_entries', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['queue_number']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
            $table->dropIndex(['is_pinned']);
            $table->dropIndex(['expires_at']);
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['end_at']);
        });

        Schema::table('point_transactions', function (Blueprint $table) {
            $table->dropIndex(['member_id', 'created_at']);
        });
    }
};
