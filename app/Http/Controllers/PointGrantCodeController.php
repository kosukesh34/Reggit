<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\UseCases\Member\RedeemPointQrUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointGrantCodeController extends Controller
{
    public function __invoke(Request $request, string $token, UserService $users, RedeemPointQrUseCase $useCase): JsonResponse
    {
        $user = $users->findUser($request);
        abort_if($user === null, 401, '会員アプリにログインしてから受け取ってください。');

        return response()->json($useCase->handle($user, $token));
    }
}
