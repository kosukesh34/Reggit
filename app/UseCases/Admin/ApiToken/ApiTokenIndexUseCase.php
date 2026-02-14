<?php

namespace App\UseCases\Admin\ApiToken;

use App\Models\ApiToken;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApiTokenIndexUseCase
{
    public function handle(): LengthAwarePaginator
    {
        return ApiToken::with('project')->orderByDesc('id')->paginate(20);
    }
}
