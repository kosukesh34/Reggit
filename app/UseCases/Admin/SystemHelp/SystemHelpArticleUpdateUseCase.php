<?php

namespace App\UseCases\Admin\SystemHelp;

use App\Models\HelpArticle;
use App\UseCases\Admin\SystemHelp\Data\SystemHelpArticleInput;

class SystemHelpArticleUpdateUseCase
{
    public function handle(int $id, SystemHelpArticleInput $input): HelpArticle
    {
        $article = HelpArticle::query()->findOrFail($id);
        $article->update($input->toAttributes());

        return $article;
    }
}
