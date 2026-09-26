<?php

namespace App\UseCases\Admin\SystemHelp;

use App\Models\HelpArticle;

class SystemHelpArticleFindUseCase
{
    public function handle(int $id): HelpArticle
    {
        return HelpArticle::query()->findOrFail($id);
    }
}
