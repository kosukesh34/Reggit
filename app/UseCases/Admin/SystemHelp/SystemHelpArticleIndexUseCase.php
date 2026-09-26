<?php

namespace App\UseCases\Admin\SystemHelp;

use App\Models\HelpArticle;
use Illuminate\Database\Eloquent\Collection;

class SystemHelpArticleIndexUseCase
{
    /** @return Collection<int, HelpArticle> */
    public function handle(): Collection
    {
        return HelpArticle::query()->orderBy('sort_order')->orderBy('id')->get();
    }
}
