<?php

namespace Database\Seeders;

use App\Models\HelpArticle;
use Illuminate\Database\Seeder;

class HelpArticleSeeder extends Seeder
{
    /** @return list<array<string, mixed>> */
    public function articles(): array
    {
        return json_decode(file_get_contents(__DIR__.'/data/help-articles.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function body(array $article): string
    {
        $body = $article['body'] ?? '';
        foreach ($article['steps'] as $index => $step) {
            $body .= "\n\n## ".($index + 1).'. '.$step['title']."\n\n".$step['body']."\n\n![".$step['caption'].'](/'.$step['image'].")\n\n";
        }
        if ($article['note'] !== '') {
            $body .= "\n\n## 注意\n\n".$article['note'];
        }

        return trim($body);
    }

    public function run(): void
    {
        foreach ($this->articles() as $index => $article) {
            HelpArticle::query()->firstOrCreate(['slug' => $article['slug']], [
                'title' => $article['title'],
                'summary' => $article['summary'],
                'category' => $article['category'] ?? 'guide',
                'body' => $this->body($article),
                'sort_order' => $index,
                'is_published' => true,
            ]);
        }
    }
}
