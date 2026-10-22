<?php

namespace App\Services\Shop;

use App\Models\MemberPasskey;
use App\Models\User;
use App\Support\PasskeyOrigin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

/** 会員（User）向けパスキー。管理者の passkeys テーブルとは分離。 */
final class MemberPasskeyService
{
    public function __construct(
        private GenerateRegistrationOptions $generateRegistrationOptions,
        private GenerateVerificationOptions $generateVerificationOptions,
    ) {}

    /**
     * @return array<array-key, mixed>
     */
    public function registrationOptions(User $user, Request $request): array
    {
        PasskeyOrigin::apply($request);
        $options = ($this->generateRegistrationOptions)($user);
        $request->session()->put('member_passkey.registration_options', WebAuthn::toJson($options));

        return WebAuthn::toBrowserArray($options);
    }

    public function store(User $user, string $name, PublicKeyCredential $credential, Request $request): MemberPasskey
    {
        PasskeyOrigin::apply($request);
        $serialized = $request->session()->pull('member_passkey.registration_options');
        if (! is_string($serialized) || $serialized === '') {
            throw InvalidPasskeyException::make('パスキー登録の有効期限が切れました。もう一度お試しください。');
        }
        /** @var PublicKeyCredentialCreationOptions $options */
        $options = WebAuthn::fromJson($serialized, PublicKeyCredentialCreationOptions::class);
        if (! $credential->response instanceof AuthenticatorAttestationResponse) {
            throw InvalidPasskeyException::make('パスキーを登録できませんでした。');
        }
        $source = WebAuthn::attestationValidator()->check(
            authenticatorAttestationResponse: $credential->response,
            publicKeyCredentialCreationOptions: $options,
            host: Passkeys::relyingPartyId(),
        );
        $credentialId = Base64UrlSafe::encodeUnpadded($source->publicKeyCredentialId);
        if (MemberPasskey::query()->where('credential_id', $credentialId)->exists()) {
            throw InvalidPasskeyException::make('このパスキーは登録できません。');
        }

        return $user->memberPasskeys()->create([
            'name' => $name !== '' ? $name : 'パスキー',
            'credential_id' => $credentialId,
            'credential' => json_decode(WebAuthn::toJson($source), true, flags: JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function loginOptions(?User $user, Request $request): array
    {
        PasskeyOrigin::apply($request);
        $options = ($this->generateVerificationOptions)($user);
        $request->session()->put('member_passkey.verification_options', WebAuthn::toJson($options));

        return WebAuthn::toBrowserArray($options);
    }

    public function verify(PublicKeyCredential $credential, Request $request): User
    {
        PasskeyOrigin::apply($request);
        $serialized = $request->session()->pull('member_passkey.verification_options');
        if (! is_string($serialized) || $serialized === '') {
            throw InvalidPasskeyException::make('パスキー確認の有効期限が切れました。もう一度お試しください。');
        }
        /** @var PublicKeyCredentialRequestOptions $options */
        $options = WebAuthn::fromJson($serialized, PublicKeyCredentialRequestOptions::class);
        if (! $credential->response instanceof AuthenticatorAssertionResponse) {
            throw InvalidPasskeyException::make('パスキーを確認できませんでした。');
        }

        return DB::transaction(function () use ($credential, $options): User {
            $credentialId = Base64UrlSafe::encodeUnpadded($credential->rawId);
            $passkey = MemberPasskey::query()->where('credential_id', $credentialId)->lockForUpdate()->first();
            if ($passkey === null) {
                throw InvalidPasskeyException::make('登録されていないパスキーです。');
            }
            $source = WebAuthn::fromJson(
                json_encode($passkey->credential, JSON_THROW_ON_ERROR),
                CredentialRecord::class,
            );
            $updated = WebAuthn::assertionValidator()->check(
                credentialRecord: $source,
                authenticatorAssertionResponse: $credential->response,
                publicKeyCredentialRequestOptions: $options,
                host: Passkeys::relyingPartyId(),
                userHandle: $source->userHandle,
            );
            $passkey->forceFill([
                'credential' => json_decode(WebAuthn::toJson($updated), true, flags: JSON_THROW_ON_ERROR),
                'last_used_at' => now(),
            ])->save();
            $user = $passkey->user;
            if (! $user instanceof User) {
                throw InvalidPasskeyException::make('会員が見つかりません。');
            }

            return $user;
        });
    }
}
