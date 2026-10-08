<?php

namespace App\UseCases\Admin\Help;

use App\Models\HelpArticle;

class AdminHelpIndexUseCase
{
    /** @return list<array{slug: string, title: string, summary: string, category: string}> */
    public function handle(string $query): array
    {
        $rows = [];
        $needle = mb_strtolower(trim($query));
        $stored = HelpArticle::query()->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get();
        foreach ($stored as $article) {
            $haystack = mb_strtolower($article->title.' '.$article->summary.' '.$article->body);
            if ($needle !== '' && str_contains($haystack, $needle) === false) {
                continue;
            }
            $rows[] = [
                'slug' => $article->slug,
                'title' => $article->title,
                'summary' => $article->summary,
                'category' => $article->category,
            ];
        }

        return $rows;
    }
}
