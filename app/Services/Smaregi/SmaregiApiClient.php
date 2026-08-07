<?php

namespace App\Services\Smaregi;

use App\Models\Settings\ProjectProfileSmaregiSetting;
use App\Models\ShopSetting;
use App\Models\SmaregiContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * スマレジ・プラットフォームAPI の HTTP / 認証コア。
 */
class SmaregiApiClient
{
    private const CACHE_KEY_PREFIX = 'smaregi_access_token:';

    private const CACHE_BUFFER_SECONDS = 60;

    private ?string $lastErrorDetail = null;

    private ?int $lastHttpStatus = null;

    public function getLastErrorDetail(): ?string
    {
        return $this->lastErrorDetail;
    }

    public function getLastHttpStatus(): ?int
    {
        return $this->lastHttpStatus;
    }

    public function clearLastErrorDetail(): void
    {
        $this->lastErrorDetail = null;
    }

    public function getAppAccessToken(string $contractId): ?string
    {
        $credentials = $this->resolveCredentialsForContract($contractId);
        $clientId = $credentials['client_id'];
        $clientSecret = $credentials['client_secret'];
        $cacheKey = self::CACHE_KEY_PREFIX.$contractId.':'.$clientId;
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $idBaseUrl = rtrim(config('smaregi.id_base_url'), '/');
        $scope = config('smaregi.scope');

        if ($clientId === '' || $clientSecret === '') {
            Log::warning('[Smaregi] token skip client not configured', ['smaregi_contract_id' => $contractId]);

            return null;
        }

        $url = "{$idBaseUrl}/app/{$contractId}/token";
        try {
            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(30)
                ->connectTimeout(15)
                ->post($url, [
                    'grant_type' => 'client_credentials',
                    'scope' => $scope,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('[Smaregi] token request connection failed (timeout or unreachable)', [
                'smaregi_contract_id' => $contractId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::error('[Smaregi] token request failed', [
                'smaregi_contract_id' => $contractId,
                'status' => $response->status(),
            ]);

            return null;
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        if (! is_string($token) || $token === '') {
            return null;
        }

        Cache::put($cacheKey, $token, $expiresIn - self::CACHE_BUFFER_SECONDS);

        return $token;
    }

    /**
     * 契約IDに紐づくプロジェクトの credentials を優先し、なければ .env にフォールバックする。
     *
     * @return array{client_id: string, client_secret: string}
     */
    public function resolveCredentialsForContract(string $contractId): array
    {
        $projectId = $this->resolveProjectIdForContract($contractId);
        if ($projectId !== null) {
            $clientId = ShopSetting::getValue('smaregi_client_id', null, $projectId);
            $clientSecret = ShopSetting::getValue('smaregi_client_secret', null, $projectId);
            $clientId = is_string($clientId) ? trim($clientId) : '';
            $clientSecret = is_string($clientSecret) ? trim($clientSecret) : '';
            if ($clientId !== '' && $clientSecret !== '') {
                return ['client_id' => $clientId, 'client_secret' => $clientSecret];
            }
        }

        return [
            'client_id' => trim((string) config('smaregi.client_id', '')),
            'client_secret' => trim((string) config('smaregi.client_secret', '')),
        ];
    }

    private function resolveProjectIdForContract(string $contractId): ?int
    {
        $linked = SmaregiContract::where('contract_id', $contractId)
            ->whereNotNull('project_id')
            ->value('project_id');
        if (is_numeric($linked)) {
            return (int) $linked;
        }

        $setting = ProjectProfileSmaregiSetting::query()
            ->where('smaregi_contract_id', $contractId)
            ->value('project_id');

        return is_numeric($setting) ? (int) $setting : null;
    }

    /**
     * プラットフォームAPI（POS 等）を呼び出す。任意のエンドポイントで利用可能。
     *
     * @param  string  $path  pos/ から始まるパス
     * @param  array<string, string|int|float|bool|array|null>  $body  GET時はクエリ、POST/PATCH/PUT時はJSONボディ
     * @return array<int|string, string|int|float|bool|array|null>|null
     */
    public function request(string $contractId, string $method, string $path, array $body = [], bool $bodyAsRequestJsonForm = false): ?array
    {
        $this->lastHttpStatus = null;
        $this->lastErrorDetail = null;
        $token = $this->getAppAccessToken($contractId);
        if ($token === null) {
            return null;
        }

        $apiBaseUrl = rtrim(config('smaregi.api_base_url'), '/');
        $path = ltrim($path, '/');
        $url = "{$apiBaseUrl}/{$contractId}/{$path}";

        $request = Http::withToken($token)
            ->acceptJson()
            ->timeout(30);

        $methodLower = strtolower($method);
        if ($methodLower === 'get') {
            $response = $request->get($url, $body);
        } elseif ($body !== [] && in_array($methodLower, ['post', 'put', 'patch'], true)) {
            if ($bodyAsRequestJsonForm) {
                $response = $request->asForm()
                    ->{$methodLower}($url, ['request_json' => json_encode($body)]);
            } else {
                $response = $request->withBody(json_encode($body), 'application/json')
                    ->{$methodLower}($url);
            }
        } else {
            $response = $request->{$methodLower}($url, $body);
        }

        $this->lastHttpStatus = $response->status();

        if (! $response->successful()) {
            $responseBody = $response->body();
            $this->lastErrorDetail = $this->parseErrorDetail($responseBody);
            $context = [
                'smaregi_contract_id' => $contractId,
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'response_body' => $responseBody !== '' ? $responseBody : null,
            ];
            if ($body !== [] && in_array(strtolower($method), ['post', 'patch'], true)) {
                if ($path === 'pos/coupons' || str_starts_with($path, 'pos/products')) {
                    $context['request_body_sent'] = $body;
                }
            }
            Log::warning('[Smaregi] API request failed', $context);

            return null;
        }

        $this->lastErrorDetail = null;

        return $response->json() ?? [];
    }

    private function parseErrorDetail(string $responseBody): ?string
    {
        if ($responseBody === '') {
            return null;
        }

        $decoded = json_decode($responseBody, true);
        if (! is_array($decoded)) {
            return null;
        }

        $detail = $decoded['detail'] ?? $decoded['message'] ?? $decoded['title'] ?? null;

        return is_string($detail) && trim($detail) !== '' ? trim($detail) : null;
    }

    /**
     * @param  array<int|string, string|int|float|bool|array|null>  $result
     * @param  list<string>  $possibleKeys
     * @return array<int, string|int|float|bool|array|null>
     */
    public function extractList(array $result, array $possibleKeys): array
    {
        if ($result === [] || array_keys($result) === range(0, count($result) - 1)) {
            return $result;
        }

        foreach ($possibleKeys as $key) {
            $list = $result[$key] ?? null;
            if (is_array($list)) {
                return $list;
            }
        }

        return [];
    }
}
