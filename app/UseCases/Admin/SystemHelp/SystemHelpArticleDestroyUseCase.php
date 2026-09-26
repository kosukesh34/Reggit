<?php

namespace App\UseCases\Admin\SystemHelp;

use App\Models\HelpArticle;

class SystemHelpArticleDestroyUseCase
{
    public function handle(int $id): void
    {
        HelpArticle::query()->findOrFail($id)->delete();
    }
}
