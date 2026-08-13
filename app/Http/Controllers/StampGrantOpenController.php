<?php

namespace App\Http\Controllers;

use App\UseCases\Stamp\StampGrantOpenUseCase;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StampGrantOpenController extends Controller
{
    public function __invoke(string $token, StampGrantOpenUseCase $useCase): RedirectResponse
    {
        $result = $useCase->handle($token);
        if ($result->isSuccess !== true || $result->redirectUrl === null) {
            throw new HttpException($result->status, $result->message);
        }

        return redirect()->away($result->redirectUrl);
    }
}
