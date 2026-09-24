<?php

namespace App\Http\Controllers\Admin;

use App\Models\HelpArticle;
use App\Support\HelpManual;
use App\Support\HelpMarkdown;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends AdminBaseController
{
    public function __construct(
        private HelpManual $helpManual,
        private HelpMarkdown $helpMarkdown,
    ) {}

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        return view('admin.help.index', [
            'articles' => $this->publishedArticles($query),
            'query' => $query,
        ]);
    }

    public function show(Request $request): View
    {
        $article = $this->findPublished((string) $request->route('slug'));
        abort_if($article === null, 404);
        $body = $article['body'] ?? '';

        return view('admin.help.show', [
            'article' => $article,
            'html' => $body !== '' ? $this->helpMarkdown->toHtml($body) : '',
        ]);
    }

    /**
     * @return list<array{slug: string, title: string, summary: string, category: string}>
     */
    private function publishedArticles(string $query): array
    {
        $rows = [];
        foreach ($this->helpManual->matching($query) as $article) {
            $rows[] = [
                'slug' => $article['slug'],
                'title' => $article['title'],
                'summary' => $article['summary'],
                'category' => $article['category'] ?? 'guide',
            ];
        }
        $needle = mb_strtolower($query);
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

    /**
     * @return array{slug: string, title: string, summary: string, note: string, steps: list<array<string, string>>, body: string}|null
     */
    private function findPublished(string $slug): ?array
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
