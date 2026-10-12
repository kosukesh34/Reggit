<?php

use App\Models\HelpArticle;
use Database\Seeders\HelpArticleSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $seeder = new HelpArticleSeeder;
        $previous = json_decode(file_get_contents(database_path('seeders/data/help-articles-v1.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($seeder->articles() as $article) {
            if (! array_key_exists($article['slug'], $previous)) {
                continue;
            }
            // Only upgrade the original seed text; preserve administrator edits and publication status.
            HelpArticle::query()->where('slug', $article['slug'])
                ->where('body', $previous[$article['slug']])
                ->update(['body' => $seeder->body($article), 'summary' => $article['summary']]);
        }
    }

    public function down(): void {}
};
