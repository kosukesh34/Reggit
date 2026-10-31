<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\UseCases\StoreApp\StoreAppEnrollmentUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreAppAuthController extends Controller
{
    public function __construct(private StoreAppEnrollmentUseCase $enrollment) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enrollment_token' => ['required', 'string', 'max:100'],
            'app' => ['required', Rule::in(['kitchen', 'waiting'])],
            'device_name' => ['nullable', 'string', 'max:100'],
            'device_key' => ['required', 'string', 'min:32', 'max:255'],
        ]);

        return response()->json($this->enrollment->login($data));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->enrollment->logout($request->user()?->currentAccessToken());

        return response()->json(['message' => 'ログアウトしました。']);
    }
}
