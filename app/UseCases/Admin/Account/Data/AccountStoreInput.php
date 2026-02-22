<?php

namespace App\UseCases\Admin\Account\Data;

/**
 * アカウント作成 UseCase への入力。検証済み。不変。
 *
 * @param array<string, string|int|float|bool|array|null> $accountPayload 検証済みアカウント項目（login_id, name, role 等）
 * @param array<int, int> $projectIds
 * @param array<int, string> $permissions
 */
class AccountStoreInput
{
    public function __construct(
        public array $accountPayload,
        public array $projectIds,
        public array $permissions
    ) {
    }
}
