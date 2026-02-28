<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kana', 255)->nullable()->after('name')->comment('名前（カナ）・スマレジ連携用');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->string('postal_code', 16)->nullable()->after('address')->comment('郵便番号');
            $table->string('prefecture', 64)->nullable()->after('postal_code')->comment('都道府県');
            $table->string('city', 128)->nullable()->after('prefecture')->comment('市区町村');
            $table->string('street', 256)->nullable()->after('city')->comment('番地・建物名');
            $table->string('sex', 16)->nullable()->after('street')->comment('性別 1=男 2=女');
            $table->json('profile_extra')->nullable()->after('sex')->comment('任意項目の値 key=>value');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kana');
        });
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['postal_code', 'prefecture', 'city', 'street', 'sex', 'profile_extra']);
        });
    }
};
