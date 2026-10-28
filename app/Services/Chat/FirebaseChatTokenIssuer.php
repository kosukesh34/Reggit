<?php

namespace App\Services\Chat;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;

/**
 * チャット用の Firebase カスタムトークンを発行する。
 * 読み書きできる会話はこのクレームだけで決まり、firestore.rules / storage.rules が検証する。
 */
class FirebaseChatTokenIssuer
{
    /** 退会・権限変更を Firebase 側へ反映するまでの上限。ID トークンを更新してもクレームは変わらないため期限を持たせる */
    public const SESSION_SECONDS = 8 * 3600;

    private const AUDIENCE = 'https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit';

    public function isConfigured(): bool
    {
        return $this->firebaseProjectId() !== ''
            && trim((string) config('services.firebase.web_api_key')) !== ''
            && $this->credentials() !== null;
    }

    /**
     * @param  list<int>  $storeIds  会員の利用店舗。担当店舗スタッフの閲覧範囲の判定に使う
     * @return array{token: string, firebase: array<string, string>}
     */
    public function forMember(int $projectId, int $memberId, string $memberName, array $storeIds): array
    {
        return $this->issue("m-{$projectId}-{$memberId}", [
            'role' => 'member',
            'pid' => (string) $projectId,
            'mid' => (string) $memberId,
            'label' => Str::limit($memberName, 40, ''),
            'stores' => array_values(array_map('intval', $storeIds)),
        ]);
    }

    /**
     * @param  list<int>|null  $storeIds  null は全店舗の会員を閲覧できる
     * @return array{token: string, firebase: array<string, string>}
     */
    public function forStaff(int $projectId, int $adminUserId, string $staffName, ?array $storeIds): array
    {
        return $this->issue("a-{$adminUserId}", [
            'role' => 'staff',
            'pid' => (string) $projectId,
            'label' => Str::limit($staffName, 40, ''),
            'all_stores' => $storeIds === null,
            'stores' => array_values(array_map('intval', $storeIds ?? [])),
        ]);
    }

    /**
     * @param  array<string, mixed>  $claims
     * @return array{token: string, firebase: array<string, string>}
     */
    private function issue(string $uid, array $claims): array
    {
        $credentials = $this->credentials() ?? throw new \RuntimeException('Firebase credentials are not configured');
        $now = time();
        $claims['until'] = ($now + self::SESSION_SECONDS) * 1000;
        $projectId = $this->firebaseProjectId();

        return [
            'token' => JWT::encode([
                'iss' => $credentials['client_email'],
                'sub' => $credentials['client_email'],
                'aud' => self::AUDIENCE,
                'iat' => $now,
                'exp' => $now + 3600,
                'uid' => $uid,
                'claims' => $claims,
            ], $credentials['private_key'], 'RS256'),
            'firebase' => [
                'apiKey' => trim((string) config('services.firebase.web_api_key')),
                'authDomain' => $projectId.'.firebaseapp.com',
                'projectId' => $projectId,
                'appId' => trim((string) config('services.firebase.web_app_id')),
                // 未設定なら画像の送受信を無効にする（Storage を使わない運用）
                ...(filled(config('services.firebase.storage_bucket')) ? ['storageBucket' => trim((string) config('services.firebase.storage_bucket'))] : []),
                ...(filled(config('services.firebase.emulator_host')) ? ['emulatorHost' => (string) config('services.firebase.emulator_host')] : []),
            ],
        ];
    }

    private function firebaseProjectId(): string
    {
        return trim((string) config('services.firebase.project_id'));
    }

    /** @return array{client_email: string, private_key: string}|null */
    private function credentials(): ?array
    {
        $raw = trim((string) config('services.firebase.credentials'));
        if ($raw === '') {
            return null;
        }
        $path = str_starts_with($raw, '/') ? $raw : base_path($raw);
        $json = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;

        return is_array($json) && is_string($json['client_email'] ?? null) && is_string($json['private_key'] ?? null)
            ? ['client_email' => $json['client_email'], 'private_key' => $json['private_key']]
            : null;
    }
}
