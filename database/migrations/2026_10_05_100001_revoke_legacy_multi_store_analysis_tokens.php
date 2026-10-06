<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy tokens do not record the issuer's authority. Headquarters must reissue them.
        DB::table('api_tokens')->where('scope', 'analysis')
            ->whereIn('project_id', DB::table('stores')->select('project_id')->groupBy('project_id')->havingRaw('COUNT(*) > 1'))
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        // Revoked bearer credentials must never be reactivated by a rollback.
    }
};
