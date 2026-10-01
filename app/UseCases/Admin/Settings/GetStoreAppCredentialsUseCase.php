<?php

namespace App\UseCases\Admin\Settings;

use App\Models\StoreAppCredential;
use App\UseCases\Admin\Settings\Data\StoreAppCredentialView;

class GetStoreAppCredentialsUseCase
{
    /** @return array<string, StoreAppCredentialView> */
    public function handle(int $projectId): array
    {
        return StoreAppCredential::query()->where('project_id', $projectId)->get()->mapWithKeys(fn ($credential) => [
            $credential->app => new StoreAppCredentialView($credential->claimed_at ? '登録済み: '.$credential->device_name : ($credential->enrollment_token_hash ? '登録待ち' : '未登録')),
        ])->all();
    }
}
