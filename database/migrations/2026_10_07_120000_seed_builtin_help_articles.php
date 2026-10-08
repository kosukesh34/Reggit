<?php

use Database\Seeders\HelpArticleSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new HelpArticleSeeder)->run();
    }

    public function down(): void
    {
        // Preserve articles and administrator edits when rolling back.
    }
};
