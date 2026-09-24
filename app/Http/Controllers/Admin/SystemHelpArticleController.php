<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use App\Support\HelpManual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemHelpArticleController extends Controller
{
    public function __construct(
        private HelpManual $helpManual,
    ) {}

    public function index(): View
    {
        $articles = HelpArticle::query()->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.system-help.index', ['articles' => $articles]);
    }

    public function create(): View
    {
        return view('admin.system-help.form', ['article' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $article = HelpArticle::query()->create($this->validated($request, null));

        return redirect()
            ->route('system-admin.help-articles.edit', ['id' => $article->id])
            ->with('success', 'ヘルプを追加しました');
    }

    public function edit(string $id): View
    {
        return view('admin.system-help.form', [
            'article' => HelpArticle::query()->findOrFail((int) $id),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $article = HelpArticle::query()->findOrFail((int) $id);
        $article->update($this->validated($request, $article));

        return redirect()
            ->route('system-admin.help-articles.edit', ['id' => $article->id])
            ->with('success', 'ヘルプを更新しました');
    }

    public function destroy(string $id): RedirectResponse
    {
        HelpArticle::query()->findOrFail((int) $id)->delete();

        return redirect()
            ->route('system-admin.help-articles.index')
            ->with('success', 'ヘルプを削除しました');
    }

    /**
     * @return array{slug: string, title: string, summary: string, category: string, body: string, sort_order: int, is_published: bool}
     */
    private function validated(Request $request, ?HelpArticle $current): array
    {
        $reserved = [];
        foreach ($this->helpManual->articles() as $article) {
            $reserved[] = $article['slug'];
        }
        $unique = Rule::unique('help_articles', 'slug');
        if ($current !== null) {
            $unique = $unique->ignore($current->id);
        }
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn($reserved), $unique],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:500'],
            'category' => ['required', Rule::in(['guide', 'faq'])],
            'body' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        return [
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'summary' => $validated['summary'],
            'category' => $validated['category'],
            'body' => $validated['body'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_published' => $request->boolean('is_published'),
        ];
    }
}
