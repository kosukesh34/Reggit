<?php

namespace App\Http\Controllers;

use App\Services\JapanPostApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PostalCodeController extends Controller
{
    /**
     * 郵便番号で住所を検索（日本郵便API）
     */
    public function __invoke(Request $request): JsonResponse
    {
        $postalCode = $request->input('postal_code') ?? $request->query('postal_code') ?? $request->input('q') ?? $request->query('q') ?? '';
        $postalCode = is_string($postalCode) ? trim($postalCode) : '';
        $postalCode = preg_replace('/\D/', '', $postalCode);
        if ($postalCode === '' || strlen($postalCode) < 3) {
            return response()->json(['addresses' => []], 200);
        }

        try {
            $service = JapanPostApiService::fromConfig();
            $result = $service->searchByPostalCode($postalCode);
            $addresses = isset($result['_error']) ? [] : $result;
            $error = isset($result['_error']) ? $result['_error'] : null;

            return response()->json(array_filter(['addresses' => $addresses, 'error' => $error]), 200);
        } catch (\Throwable $e) {
            Log::error('PostalCode lookup failed', [
                'postal_code' => $postalCode,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['addresses' => [], 'error' => '住所の取得に失敗しました'], 200);
        }
    }
}
