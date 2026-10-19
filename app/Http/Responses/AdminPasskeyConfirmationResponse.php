<?php

namespace App\Http\Responses;

use App\Models\AdminUser;
use App\Support\AdminPasskeyAudit;
use App\Support\AdminPasskeyGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Contracts\PasskeyConfirmationResponse as PasskeyConfirmationResponseContract;
use Symfony\Component\HttpFoundation\Response;

class AdminPasskeyConfirmationResponse implements PasskeyConfirmationResponseContract
{
    public function toResponse($request): Response
    {
        AdminPasskeyGate::markPasskeyVerified();
        $admin = Auth::guard('admin')->user();
        if ($admin instanceof AdminUser) {
            AdminPasskeyAudit::record($admin, 'passkey_verified', [], 'passkey.confirm');
        }
        $redirect = redirect()->intended(route('admin.root'))->getTargetUrl();

        if ($request instanceof Request && $request->wantsJson()) {
            return new JsonResponse(['redirect' => $redirect], 200);
        }

        return redirect()->to($redirect);
    }
}
