<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Store;
use App\Models\StoreAppCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreAppAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enrollment_token' => ['required', 'string', 'max:100'],
            'app' => ['required', Rule::in(['kitchen', 'waiting'])],
            'device_name' => ['nullable', 'string', 'max:100'],
            'device_key' => ['required', 'string', 'min:32', 'max:255'],
        ]);

        [$credential, $token] = DB::transaction(function () use ($data): array {
            $credential = StoreAppCredential::withoutGlobalScopes()
                ->with('project')
                ->where('app', $data['app'])
                ->where('enrollment_token_hash', hash('sha256', trim($data['enrollment_token'])))
                ->lockForUpdate()
                ->first();
            if ($credential === null || $credential->claimed_at !== null || ! $credential->project?->isServiceAvailable() || ! Store::where('project_id', $credential->project_id)->whereKey($credential->store_id)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'enrollment_token' => ['登録コードが無効または使用済みです。'],
                ]);
            }

            $admin = AdminUser::query()->findOrFail($credential->admin_user_id);
            $project = $credential->project;
            $token = $admin->createToken(
                $this->tokenName($data['app'], (int) $project->id, (string) ($data['device_name'] ?? 'iPad')),
                ["app:{$data['app']}", "project:{$project->id}", "store:{$credential->store_id}"],
            );
            $credential->update([
                'personal_access_token_id' => $token->accessToken->id,
                'enrollment_token_hash' => null,
                'device_key_hash' => hash('sha256', $data['device_key']),
                'device_name' => $data['device_name'] ?? 'iPad',
                'claimed_at' => now(),
            ]);

            return [$credential->fresh('project'), $token];
        });
        $project = $credential->project;

        return response()->json([
            'token' => $token->plainTextToken,
            'project' => [
                'id' => (int) $project->id,
                'key' => $project->project_key,
                'name' => $project->name,
            ],
            'store_id' => $credential->store_id,
            'admin' => ['name' => AdminUser::query()->find($credential->admin_user_id)?->name],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->user()?->currentAccessToken();
        if ($accessToken !== null) {
            StoreAppCredential::withoutGlobalScopes()->where('personal_access_token_id', $accessToken->id)->update([
                'personal_access_token_id' => null,
                'device_key_hash' => null,
                'claimed_at' => null,
            ]);
            $accessToken->delete();
        }

        return response()->json(['message' => 'ログアウトしました。']);
    }

    private function tokenName(string $app, int $projectId, string $deviceName): string
    {
        return "store-app:{$app}:{$projectId}:".mb_substr(trim($deviceName), 0, 50);
    }
}
