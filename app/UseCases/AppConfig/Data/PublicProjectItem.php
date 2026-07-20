<?php

namespace App\UseCases\AppConfig\Data;

/**
 * 端末向けプロジェクト一覧の1件。
 */
class PublicProjectItem
{
    public function __construct(
        public readonly string $projectKey,
        public readonly string $name,
    ) {}

    /**
     * @return array{project_key: string, name: string}
     */
    public function toArray(): array
    {
        return [
            'project_key' => $this->projectKey,
            'name' => $this->name,
        ];
    }
}
