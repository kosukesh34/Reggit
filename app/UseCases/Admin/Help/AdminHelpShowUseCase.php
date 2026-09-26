<?php

namespace App\UseCases\Admin\Help;

use App\Models\HelpArticle;
use App\Support\HelpManual;

class AdminHelpShowUseCase
{
    public function __construct(private HelpManual $helpManual) {}

    /** @return array{slug: string, title: string, summary: string, note: string, steps: list<array<string, string>>, body: string}|null */
    public function handle(string $slug): ?array
    {
        $builtin = $this->helpManual->find($slug);
        if ($builtin !== null) {
            $builtin['body'] = $builtin['body'] ?? '';

            return $builtin;
        }
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
