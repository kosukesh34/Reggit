<?php

namespace App\Http\Responses;

use App\Support\AdminPasskeyGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyRegistrationResponse as PasskeyRegistrationResponseContract;
use Laravel\Passkeys\Passkey;
use Symfony\Component\HttpFoundation\Response;

class AdminPasskeyRegistrationResponse implements PasskeyRegistrationResponseContract
{
    protected ?Passkey $passkey = null;

    public function withPasskey(Passkey $passkey): static
    {
        $this->passkey = $passkey;

        return $this;
    }

    public function toResponse($request): Response
    {
        $wasPending = AdminPasskeyGate::requiresPasskeyStep();
        $wasRemoteEnroll = AdminPasskeyGate::isRemoteEnroll();
        if ($wasPending || $wasRemoteEnroll) {
            AdminPasskeyGate::markPasskeyVerified();
        }

        $redirect = ($wasPending && ! $wasRemoteEnroll)
            ? redirect()->intended(route('admin.root'))->getTargetUrl()
            : route('admin.passkey.settings');

        if ($request instanceof Request && $request->wantsJson()) {
            $data = [
                'status' => 'passkey-registered',
                'redirect' => $redirect,
            ];
            if ($this->passkey instanceof Passkey) {
                $data['id'] = (string) $this->passkey->id;
                $data['name'] = $this->passkey->name;
            }

            return new JsonResponse($data, 200);
        }

        return redirect()->to($redirect)->with('status', 'パスキーを登録しました。');
    }
}
