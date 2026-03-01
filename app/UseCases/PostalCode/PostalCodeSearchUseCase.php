<?php

namespace App\UseCases\PostalCode;

use App\Services\JapanPostApiService;
use App\UseCases\PostalCode\Data\PostalCodeSearchOutput;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 郵便番号で住所を検索する UseCase。
 * 日本郵便 API → 失敗時は zipcloud でフォールバックする。
 */
class PostalCodeSearchUseCase
{
    private const ZIPCLOUD_URL = 'https://zipcloud.ibsnet.co.jp/api/search';

    public function handle(string $postalCode): PostalCodeSearchOutput
    {
        $digits = preg_replace('/\D/', '', $postalCode);
        if ($digits === '' || strlen($digits) < 3) {
            return new PostalCodeSearchOutput([], null);
        }

        $addresses = [];
        $error = null;

        try {
            $service = JapanPostApiService::fromConfig();
            $result = $service->searchByPostalCode($digits);
            if (isset($result['_error'])) {
                $error = $result['_error'];
            } else {
                $addresses = $result;
            }
        } catch (\Throwable $e) {
            Log::warning('PostalCode JapanPost failed', [
                'postal_code' => $digits,
                'message' => $e->getMessage(),
            ]);
            $error = $e->getMessage();
        }

        if ($addresses === [] && strlen($digits) >= 7) {
            $fallback = $this->searchByZipcloud($digits);
            if ($fallback !== []) {
                $addresses = $fallback;
                $error = null;
            }
        }

        return new PostalCodeSearchOutput($addresses, $error);
    }

    /**
     * @return array<int, array{pref_name: string, city_name: string, town_name: string, block_name: string|null, full_address: string}>
     */
    private function searchByZipcloud(string $zipcode): array
    {
        $zipcode = preg_replace('/\D/', '', $zipcode);
        if (strlen($zipcode) < 7) {
            return [];
        }

        try {
            $response = Http::timeout(10)->get(self::ZIPCLOUD_URL, ['zipcode' => $zipcode]);
            if (! $response->successful()) {
                return [];
            }
            $data = $response->json();
            if (! is_array($data) || ($data['status'] ?? 0) !== 200) {
                return [];
            }
            $results = $data['results'] ?? null;
            if (! is_array($results) || $results === []) {
                return [];
            }

            $list = [];
            foreach ($results as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $pref = (string) ($row['address1'] ?? '');
                $city = (string) ($row['address2'] ?? '');
                $town = (string) ($row['address3'] ?? '');
                $full = $pref.$city.$town;
                if ($full === '') {
                    continue;
                }
                $list[] = [
                    'pref_name' => $pref,
                    'city_name' => $city,
                    'town_name' => $town,
                    'block_name' => null,
                    'full_address' => $full,
                ];
            }

            return $list;
        } catch (\Throwable $e) {
            Log::warning('PostalCode zipcloud fallback failed', [
                'zipcode' => $zipcode,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
