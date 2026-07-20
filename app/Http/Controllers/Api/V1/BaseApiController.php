<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BaseApiController extends Controller
{
    protected function success(array $data = [], ?string $message = null, int $status = Response::HTTP_OK, array $meta = []): JsonResponse
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

    protected function error(string $message, int $status = Response::HTTP_BAD_REQUEST, array $data = [], array $errors = [], array $meta = []): JsonResponse
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
