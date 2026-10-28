<?php

namespace App\Services;

use App\Util\LogHelper;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

/**
 * FCM HTTP v1 送信（サービスアカウント JWT）。
 */
class FcmHttpV1Client
{
    private const TOKEN_CACHE_KEY = 'push.fcm.access_token';

    private const INVALID_ERROR_CODES = [
        'UNREGISTERED',
        'NOT_FOUND',
        'INVALID_ARGUMENT',
        'SENDER_ID_MISMATCH',
    ];

    public function __construct(private Client $http) {}

    public function isConfigured(): bool
    {
        $projectId = trim((string) config('push.fcm.project_id'));
        $credentials = $this->resolveCredentialsPath();

        return (bool) config('push.fcm.enabled')
            && $projectId !== ''
            && $credentials !== null
            && is_readable($credentials);
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<string, string>  $data
     * @return array{sent:int,failed:int,invalid_tokens:list<string>}
     */
    public function sendToTokens(
        array $tokens,
        string $title,
        string $body,
        array $data = [],
        ?string $collapseId = null,
        bool $contentAvailable = false,
        bool $mutableContent = false,
        ?int $badge = null,
    ): array {
        $sent = 0;
        $failed = 0;
        $invalidTokens = [];

        foreach ($tokens as $token) {
            $token = trim((string) $token);
            if ($token === '') {
                $failed++;

                continue;
            }

            try {
                $result = $this->sendOne($token, $title, $body, $data, $collapseId, $contentAvailable, $mutableContent, $badge);
            } catch (\Throwable $e) {
                LogHelper::error('[Push] FCM v1 request failed', [
                    'error' => $e->getMessage(),
                    'token_prefix' => substr($token, 0, 12),
                ]);
                $failed++;

                continue;
            }

            if ($result['ok']) {
                $sent++;
            } else {
                $failed++;
                if ($result['invalid']) {
                    $invalidTokens[] = $token;
                }
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'invalid_tokens' => array_values(array_unique($invalidTokens)),
        ];
    }

    /**
     * @param  array<string, string>  $data
     * @return array{ok:bool,invalid:bool,name:?string,error:?string}
     */
    private function sendOne(
        string $token,
        string $title,
        string $body,
        array $data,
        ?string $collapseId,
        bool $contentAvailable,
        bool $mutableContent,
        ?int $badge = null,
    ): array {
        $projectId = trim((string) config('push.fcm.project_id'));
        $message = [
            'token' => $token,
            'data' => $this->stringifyData($data),
        ];

        if (! $contentAvailable || $title !== '' || $body !== '') {
            $message['notification'] = [
                'title' => $title,
                'body' => $body,
            ];
        }

        $android = ['priority' => 'HIGH'];
        $apns = [
            'headers' => [
                'apns-priority' => $contentAvailable ? '5' : '10',
            ],
            'payload' => [
                'aps' => [],
            ],
        ];

        if ($collapseId !== null && $collapseId !== '') {
            $android['collapse_key'] = $collapseId;
            $apns['headers']['apns-collapse-id'] = $collapseId;
            $android['notification'] = [
                'tag' => $collapseId,
                'channel_id' => 'reggit_default',
            ];
            $apns['payload']['aps']['thread-id'] = $collapseId;
        }

        if ($contentAvailable) {
            $apns['payload']['aps']['content-available'] = 1;
        }
        if ($mutableContent) {
            $apns['payload']['aps']['mutable-content'] = 1;
        }
        // アプリアイコンの数字（iOS）・通知の件数（Android）
        if ($badge !== null) {
            $apns['payload']['aps']['badge'] = $badge;
            $android['notification']['notification_count'] = $badge;
        }
        if (! $contentAvailable || $title !== '' || $body !== '') {
            $apns['payload']['aps']['alert'] = [
                'title' => $title,
                'body' => $body,
            ];
            $apns['payload']['aps']['sound'] = 'default';
        }

        $message['android'] = $android;
        $message['apns'] = $apns;

        $response = $this->http->post(
            'https://fcm.googleapis.com/v1/projects/'.$projectId.'/messages:send',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->accessToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => ['message' => $message],
                'http_errors' => false,
            ]
        );

        $status = $response->getStatusCode();
        $payload = json_decode((string) $response->getBody(), true);
        if (! is_array($payload)) {
            $payload = [];
        }

        if ($status >= 200 && $status < 300) {
            return [
                'ok' => true,
                'invalid' => false,
                'name' => isset($payload['name']) ? (string) $payload['name'] : null,
                'error' => null,
            ];
        }

        $errorStatus = (string) ($payload['error']['status'] ?? '');
        $errorCode = '';
        $details = $payload['error']['details'] ?? null;
        if (is_array($details)) {
            foreach ($details as $detail) {
                if (is_array($detail) && isset($detail['errorCode'])) {
                    $errorCode = (string) $detail['errorCode'];
                    break;
                }
            }
        }

        $invalid = in_array($errorCode, self::INVALID_ERROR_CODES, true)
            || in_array($errorStatus, ['NOT_FOUND', 'INVALID_ARGUMENT'], true);

        LogHelper::error('[Push] FCM v1 send rejected', [
            'http_status' => $status,
            'error_status' => $errorStatus !== '' ? $errorStatus : null,
            'error_code' => $errorCode !== '' ? $errorCode : null,
            'message' => $payload['error']['message'] ?? null,
        ]);

        return [
            'ok' => false,
            'invalid' => $invalid,
            'name' => null,
            'error' => $errorCode !== '' ? $errorCode : $errorStatus,
        ];
    }

    private function accessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $path = $this->resolveCredentialsPath();
        if ($path === null) {
            throw new \RuntimeException('FCM credentials path is not configured');
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json) || ! isset($json['client_email'], $json['private_key'])) {
            throw new \RuntimeException('FCM credentials JSON is invalid');
        }

        $now = time();
        $jwt = JWT::encode([
            'iss' => $json['client_email'],
            'sub' => $json['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ], $json['private_key'], 'RS256');

        $response = $this->http->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
            'http_errors' => false,
        ]);
        $payload = json_decode((string) $response->getBody(), true);
        $token = is_array($payload) ? trim((string) ($payload['access_token'] ?? '')) : '';
        if ($token === '') {
            throw new \RuntimeException('Failed to obtain FCM access token');
        }

        $expiresIn = is_array($payload) && isset($payload['expires_in'])
            ? max(60, (int) $payload['expires_in'] - 60)
            : 3300;
        Cache::put(self::TOKEN_CACHE_KEY, $token, $expiresIn);

        return $token;
    }

    private function resolveCredentialsPath(): ?string
    {
        $raw = trim((string) config('push.fcm.credentials'));
        if ($raw === '') {
            return null;
        }
        if (str_starts_with($raw, '/')) {
            return $raw;
        }

        return base_path($raw);
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, string>
     */
    private function stringifyData(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $out[(string) $key] = (string) $value;
        }

        return $out;
    }
}
