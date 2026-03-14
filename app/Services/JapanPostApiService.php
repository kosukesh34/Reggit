<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JapanPostApiService
{
    private ?string $cachedToken = null;
    private string $host;
    private string $clientId;
    private string $clientSecret;

    public function __construct(string $host, string $clientId, string $clientSecret)
    {
        $this->host = $host;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public static function fromConfig(): self
    {
        $host = trim((string) (config('japanpost.host') ?: env('JAPANPOST_API_HOST') ?: getenv('JAPANPOST_API_HOST') ?: ''));
        $clientId = trim((string) (config('japanpost.client_id') ?: env('JAPANPOST_CLIENT_ID') ?: getenv('JAPANPOST_CLIENT_ID') ?: ''));
        $clientSecret = trim((string) (config('japanpost.client_secret') ?: env('JAPANPOST_CLIENT_SECRET') ?: getenv('JAPANPOST_CLIENT_SECRET') ?: ''));

        return new self($host, $clientId, $clientSecret);
    }

    /**
     * 郵便番号から住所一覧を取得（3桁以上、7桁推奨）
     *
     * @return array<int, array{pref_name: string, city_name: string, town_name: string, block_name: string|null, full_address: string}>|array{_error: string}
     */
    public function searchByPostalCode(string $postalCode): array
    {
        $code = preg_replace('/\D/', '', $postalCode);
        if (strlen($code) < 3) {
            return [];
        }

        $token = $this->getToken();
        if ($token === null) {
            return ['_error' => '住所の取得に失敗しました。設定を確認してください。'];
        }

        $url = sprintf(
            'https://%s/api/v1/searchcode/%s',
            $this->host,
            $code
        );

        $forwardedFor = $this->getForwardedFor();
        $response = Http::timeout(15)
            ->acceptJson()
            ->withToken($token)
            ->withHeaders(['x-forwarded-for' => $forwardedFor])
            ->get($url, [
                'page' => 1,
                'limit' => 20,
                'choikitype' => 1,
                'searchtype' => 1,
            ]);

        if (! $response->successful()) {
            $status = $response->status();
            Log::warning('JapanPost searchcode failed', [
                'status' => $status,
                'body' => $response->body(),
            ]);
            $body = $response->json();
            $msg = is_array($body) && isset($body['message']) ? (string) $body['message'] : '住所の取得に失敗しました';
            if ($status === 403) {
                $msg = 'アクセスが拒否されました。日本郵便のシステム情報に送信元IPを登録してください。（./scripts/test-japanpost-api.sh で送信元IPを確認できます）';
            }

            return ['_error' => $msg];
        }

        $data = $response->json();
        if ($data === null) {
            Log::warning('JapanPost searchcode: response is not JSON', ['body' => $response->body()]);

            return ['_error' => 'レスポンスの解析に失敗しました'];
        }
        if (! is_array($data)) {
            $data = (array) $data;
        }

        // スタブ等で 200 でも error_code / message で「該当なし」が返る場合
        if (isset($data['error_code']) || isset($data['message'])) {
            $msg = (string) ($data['message'] ?? '該当する郵便番号がありませんでした');

            return ['_error' => $msg];
        }

        $addresses = $data['addresses'] ?? [];
        if (! is_array($addresses)) {
            $addresses = $addresses ? (array) $addresses : [];
        }
        // API によっては addresses が配列の配列で返る場合があるためフラット化
        $flat = [];
        foreach ($addresses as $item) {
            if (is_array($item) && (isset($item['pref_name']) || isset($item['prefName']))) {
                $flat[] = $item;
            } elseif (is_array($item)) {
                foreach ($item as $a) {
                    if (is_array($a)) {
                        $flat[] = $a;
                    }
                }
            }
        }
        if ($flat === [] && $addresses !== []) {
            $flat = $addresses;
        }

        $result = [];
        $seen = [];
        foreach ($flat as $addr) {
            $addr = is_array($addr) ? $addr : (array) $addr;
            $pref = (string) ($addr['pref_name'] ?? $addr['prefName'] ?? '');
            $city = (string) ($addr['city_name'] ?? $addr['cityName'] ?? '');
            $town = (string) ($addr['town_name'] ?? $addr['townName'] ?? '');
            $block = isset($addr['block_name']) ? (string) $addr['block_name'] : (isset($addr['blockName']) ? (string) $addr['blockName'] : null);
            $parts = array_filter([$pref, $city, $town, $block]);
            $full = implode('', $parts);
            if ($full === '' || isset($seen[$full])) {
                continue;
            }
            $seen[$full] = true;
            $result[] = [
                'pref_name' => $pref,
                'city_name' => $city,
                'town_name' => $town,
                'block_name' => $block,
                'full_address' => $full,
            ];
        }

        return $result;
    }

    private function getToken(): ?string
    {
        if ($this->cachedToken !== null) {
            return $this->cachedToken;
        }

        if ($this->host === '' || $this->clientId === '' || $this->clientSecret === '') {
            Log::warning('JapanPost API credentials not configured');

            return null;
        }

        $url = sprintf('https://%s/api/v1/j/token', $this->host);
        $forwardedFor = $this->getForwardedFor();
        $response = Http::timeout(10)
            ->acceptJson()
            ->withHeaders(['x-forwarded-for' => $forwardedFor])
            ->asJson()
            ->post($url, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'secret_key' => $this->clientSecret,
            ]);

        if (! $response->successful()) {
            Log::warning('JapanPost token failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $data = $response->json();
        if (! is_array($data)) {
            $data = $data ? (array) $data : [];
        }
        $token = $data['token'] ?? $data['access_token'] ?? null;
        $this->cachedToken = is_string($token) ? trim($token) : null;

        return $this->cachedToken;
    }

    private function getForwardedFor(): string
    {
        try {
            $ip = request()->ip();
            if ($ip && $ip !== '' && $ip !== '127.0.0.1' && $ip !== '::1') {
                return $ip;
            }
        } catch (\Throwable $e) {
            // request() が利用できないコンテキスト
        }

        return '127.0.0.1';
    }
}
