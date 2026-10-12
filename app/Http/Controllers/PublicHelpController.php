<?php

namespace App\Http\Controllers;

use App\Support\HelpMarkdown;
use App\UseCases\Admin\Help\AdminHelpIndexUseCase;
use App\UseCases\Admin\Help\AdminHelpShowUseCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicHelpController extends Controller
{
    public function index(Request $request, AdminHelpIndexUseCase $index): View
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:200']]);
        $query = trim($data['q'] ?? '');

        return view('help.index', ['articles' => $index->handle($query), 'query' => $query]);
    }

    public function show(string $slug, AdminHelpShowUseCase $show, HelpMarkdown $markdown): View
    {
        $article = $show->handle($slug);
        abort_if($article === null, 404);

        return view('help.show', ['article' => $article, 'html' => $markdown->toHtml($article['body'], public: true)]);
    }
}
