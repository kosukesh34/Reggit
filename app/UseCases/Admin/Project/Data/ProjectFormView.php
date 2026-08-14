<?php

namespace App\UseCases\Admin\Project\Data;

use App\Models\Project;

/** プロジェクト作成・編集・オンボーディングフォームの表示用データ。 */
class ProjectFormView
{
    /**
     * @param  array<string, string>  $projectSettings
     */
    public function __construct(
        public ?Project $project,
        public array $projectSettings,
        public bool $isOnboarding = false,
        public bool $showAdminAccount = false,
        public bool $requireSmaregi = false,
    ) {}
}
