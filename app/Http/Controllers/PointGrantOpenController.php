<?php

namespace App\Http\Controllers;

use App\UseCases\Member\OpenPointQrUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PointGrantOpenController extends Controller
{
    public function __invoke(Request $request, string $projectKey, string $token, OpenPointQrUseCase $useCase): RedirectResponse
    {
        return redirect()->away($useCase->handle($request->attributes->get('currentProject'), $token));
    }
}
