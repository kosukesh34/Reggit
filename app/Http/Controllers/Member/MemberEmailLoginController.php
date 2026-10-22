<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Member\SendAppEmailLoginUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberEmailLoginController extends BaseApiController
{
    public function __construct(
        private SendAppEmailLoginUseCase $sendAppEmailLogin,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $this->sendAppEmailLogin->handle((string) $validated['email']);

        return $this->success([], '登録済みのメールアドレスであれば、ログイン用のリンクを送信しました。');
    }
}
