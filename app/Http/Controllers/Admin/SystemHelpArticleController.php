<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SystemHelpArticleRequest;
use App\UseCases\Admin\SystemHelp\Data\SystemHelpArticleInput;
use App\UseCases\Admin\SystemHelp\SystemHelpArticleDestroyUseCase;
use App\UseCases\Admin\SystemHelp\SystemHelpArticleFindUseCase;
use App\UseCases\Admin\SystemHelp\SystemHelpArticleIndexUseCase;
use App\UseCases\Admin\SystemHelp\SystemHelpArticleStoreUseCase;
use App\UseCases\Admin\SystemHelp\SystemHelpArticleUpdateUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SystemHelpArticleController extends Controller
{
    public function __construct(
        private SystemHelpArticleIndexUseCase $indexUseCase,
        private SystemHelpArticleFindUseCase $findUseCase,
        private SystemHelpArticleStoreUseCase $storeUseCase,
        private SystemHelpArticleUpdateUseCase $updateUseCase,
        private SystemHelpArticleDestroyUseCase $destroyUseCase,
    ) {}

    public function index(): View
    {
        return view('admin.system-help.index', ['articles' => $this->indexUseCase->handle()]);
    }

    public function create(): View
    {
        return view('admin.system-help.form', ['article' => null]);
    }

    public function store(SystemHelpArticleRequest $request): RedirectResponse
    {
        $article = $this->storeUseCase->handle($this->input($request));

        return redirect()
            ->route('system-admin.help-articles.edit', ['id' => $article->id])
            ->with('success', 'ヘルプを追加しました');
    }

    public function edit(string $id): View
    {
        return view('admin.system-help.form', [
            'article' => $this->findUseCase->handle((int) $id),
        ]);
    }

    public function update(SystemHelpArticleRequest $request, string $id): RedirectResponse
    {
        $article = $this->updateUseCase->handle((int) $id, $this->input($request));

        return redirect()
            ->route('system-admin.help-articles.edit', ['id' => $article->id])
            ->with('success', 'ヘルプを更新しました');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->destroyUseCase->handle((int) $id);

        return redirect()
            ->route('system-admin.help-articles.index')
            ->with('success', 'ヘルプを削除しました');
    }

    private function input(SystemHelpArticleRequest $request): SystemHelpArticleInput
    {
        return SystemHelpArticleInput::fromValidated($request->validated(), $request->boolean('is_published'));
    }
}
