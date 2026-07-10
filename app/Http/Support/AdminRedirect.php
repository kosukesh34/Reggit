<?php

namespace App\Http\Support;

use App\UseCases\Admin\Auth\Data\AdminRedirectResult;
use Illuminate\Http\RedirectResponse;

final class AdminRedirect
{
    public static function toResponse(AdminRedirectResult $result): RedirectResponse
    {
        return redirect()->route($result->route, $result->parameters);
    }
}
