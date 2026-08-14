<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE after_registration_deliveries MODIFY delay_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement("ALTER TABLE after_registration_deliveries MODIFY title VARCHAR(200) NOT NULL DEFAULT ''");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE after_registration_deliveries MODIFY delay_minutes SMALLINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE after_registration_deliveries MODIFY title VARCHAR(200) NOT NULL');
    }
};
