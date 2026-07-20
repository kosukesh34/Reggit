<?php

namespace App\UseCases\Onboarding\Data;

/**
 * 公開オンボーディングでのプロジェクト作成結果。
 */
class OnboardingStoreOutput
{
    /**
     * @param  array{id: int, project_key: string, name: string}  $project
     * @param  array{id: int, login_id: string, email: string}  $admin
     */
    public function __construct(
        public readonly array $project,
        public readonly array $admin,
        public readonly string $loginUrl,
    ) {}

    /**
     * @return array{
     *     project: array{id: int, project_key: string, name: string},
     *     admin: array{id: int, login_id: string, email: string},
     *     login_url: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'project' => $this->project,
            'admin' => $this->admin,
            'login_url' => $this->loginUrl,
        ];
    }
}
