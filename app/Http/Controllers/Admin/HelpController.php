<?php

namespace App\Http\Controllers\Admin;

use App\Support\HelpMarkdown;
use App\UseCases\Admin\Help\AdminHelpIndexUseCase;
use App\UseCases\Admin\Help\AdminHelpShowUseCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends AdminBaseController
{
    public function __construct(
        private AdminHelpIndexUseCase $indexUseCase,
        private AdminHelpShowUseCase $showUseCase,
        private HelpMarkdown $helpMarkdown,
    ) {}

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        return view('admin.help.index', [
            'articles' => $this->indexUseCase->handle($query),
            'query' => $query,
        ]);
    }

    public function show(Request $request): View
    {
        $article = $this->showUseCase->handle((string) $request->route('slug'));
        abort_if($article === null, 404);
        $body = $article['body'] ?? '';

        return view('admin.help.show', [
            'article' => $article,
            'html' => $body !== '' ? $this->helpMarkdown->toHtml($body) : '',
        ]);
    }
}
