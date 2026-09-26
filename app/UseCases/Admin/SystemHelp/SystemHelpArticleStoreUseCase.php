<?php

namespace App\UseCases\Admin\SystemHelp;

use App\Models\HelpArticle;
use App\UseCases\Admin\SystemHelp\Data\SystemHelpArticleInput;

class SystemHelpArticleStoreUseCase
{
    public function handle(SystemHelpArticleInput $input): HelpArticle
    {
        return HelpArticle::query()->create($input->toAttributes());
    }
}
