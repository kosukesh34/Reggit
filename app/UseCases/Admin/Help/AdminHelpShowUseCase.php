<?php

namespace App\UseCases\Admin\Help;

use App\Models\HelpArticle;

class AdminHelpShowUseCase
{
    /** @return array{slug: string, title: string, summary: string, note: string, steps: list<array<string, string>>, body: string}|null */
    public function handle(string $slug): ?array
    {
        $article = HelpArticle::query()->where('slug', $slug)->where('is_published', true)->first();
        if ($article === null) {
            return null;
        }

        return [
            'slug' => $article->slug,
            'title' => $article->title,
            'summary' => $article->summary,
            'note' => '',
            'steps' => [],
            'body' => $article->body,
        ];
    }
}
