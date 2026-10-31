<?php

namespace App\UseCases\StoreApp;

use App\Models\AdminUser;
use App\Models\Store;
use App\Models\StoreAppCredential;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Contracts\HasAbilities;

/** 店舗端末アプリ（キッチン・順番待ち）の登録コードによるログインと、端末のログアウト。 */
class StoreAppEnrollmentUseCase
{
    /**
     * 登録コードを1回だけ使って端末用トークンを発行する。
     *
     * @param  array{enrollment_token: string, app: string, device_name?: string|null, device_key: string}  $data
     * @return array{token: string, project: array{id: int, key: string, name: string}, store_id: int|null, admin: array{name: string|null}}
     */
    public function login(array $data): array
    {
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

        return [
            'token' => $token->plainTextToken,
            'project' => [
                'id' => (int) $project->id,
                'key' => $project->project_key,
                'name' => $project->name,
            ],
            'store_id' => $credential->store_id,
            'admin' => ['name' => AdminUser::query()->find($credential->admin_user_id)?->name],
        ];
    }

    /** 端末のトークンを削除し、登録情報を未使用の状態に戻す */
    public function logout(?HasAbilities $accessToken): void
    {
        if ($accessToken === null) {
            return;
        }
        StoreAppCredential::withoutGlobalScopes()->where('personal_access_token_id', $accessToken->id)->update([
            'personal_access_token_id' => null,
            'device_key_hash' => null,
            'claimed_at' => null,
        ]);
        $accessToken->delete();
    }

    private function tokenName(string $app, int $projectId, string $deviceName): string
    {
        return "store-app:{$app}:{$projectId}:".mb_substr(trim($deviceName), 0, 50);
    }
}
