<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
        });

        DB::table('shop_settings')->insert([
            [
                'key' => 'business_hours_start',
                'value' => '10:00',
                'type' => 'time',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'business_hours_end',
                'value' => '22:00',
                'type' => 'time',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'reservation_time_slots',
                'value' => json_encode(['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00']),
                'type' => 'json',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'closed_days',
                'value' => json_encode([]),
                'type' => 'json',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'advance_booking_days',
                'value' => '30',
                'type' => 'integer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }
};
