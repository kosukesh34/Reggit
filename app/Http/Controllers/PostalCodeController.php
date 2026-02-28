<?php

namespace App\Http\Controllers;

use App\Services\JapanPostApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostalCodeController extends Controller
{
    private const ZIPCLOUD_URL = 'https://zipcloud.ibsnet.co.jp/api/search';

    /**
     * 郵便番号で住所を検索（日本郵便API → 失敗時は zipcloud でフォールバック）
     */
    public function __invoke(Request $request): JsonResponse
    {
        $postalCode = $request->input('postal_code') ?? $request->query('postal_code') ?? $request->input('q') ?? $request->query('q') ?? '';
        $postalCode = is_string($postalCode) ? trim($postalCode) : '';
        $digits = preg_replace('/\D/', '', $postalCode);
        if ($digits === '' || strlen($digits) < 3) {
            return response()->json(['addresses' => []], 200);
        }

        $addresses = [];
        $error = null;

        try {
            $service = JapanPostApiService::fromConfig();
            $result = $service->searchByPostalCode($digits);
            if (! isset($result['_error'])) {
                $addresses = $result;
            } else {
                $error = $result['_error'];
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

        return response()->json(array_filter(['addresses' => $addresses, 'error' => $error]), 200);
    }

    /**
     * zipcloud（無料API）で郵便番号検索。日本郵便が使えない場合のフォールバック。
     *
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
                $full = $pref . $city . $town;
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
