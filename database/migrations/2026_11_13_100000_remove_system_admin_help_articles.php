<?php

use App\Models\HelpArticle;
use Database\Seeders\HelpArticleSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // システム管理者は運営者本人なので、店舗向けヘルプから専用の記事を外す。
        HelpArticle::query()
            ->whereIn('slug', ['system-projects', 'system-accounts', 'system-notices', 'help-management'])
            ->delete();
        // 店舗向けに書き直した既存記事を反映する（編集済みの記事は Seeder が保持する）。
        (new HelpArticleSeeder)->run();
    }

    public function down(): void
    {
        // Removed articles are restored only from a backup.
    }
};
