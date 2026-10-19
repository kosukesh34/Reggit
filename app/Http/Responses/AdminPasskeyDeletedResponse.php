<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyDeletedResponse as PasskeyDeletedResponseContract;
use Symfony\Component\HttpFoundation\Response;

class AdminPasskeyDeletedResponse implements PasskeyDeletedResponseContract
{
    public function toResponse($request): Response
    {
        if ($request instanceof Request && $request->wantsJson()) {
            return new JsonResponse(['status' => 'passkey-deleted'], 200);
        }

        return back()->with('status', 'パスキーを削除しました。');
    }
}
