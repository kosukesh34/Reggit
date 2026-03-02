<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    protected function success(array $data = [], ?string $message = null, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => [],
            'meta' => $meta,
        ];

        return response()->json($payload, $status);
    }

    protected function error(string $message, int $status = 400, array $data = [], array $errors = [], array $meta = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
            'meta' => $meta,
        ];

        return response()->json($payload, $status);
    }
}
