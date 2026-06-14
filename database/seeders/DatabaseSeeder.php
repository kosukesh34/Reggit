<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
        $this->call(DemoAdminUsersSeeder::class);

        // デフォルトプロジェクトを取得（なければ作成）
        $project = Project::where('project_key', 'default')->where('is_active', true)->first();
        if ($project === null) {
            $project = Project::where('is_active', true)->orderBy('id')->first();
        }
        if ($project === null) {
            $project = Project::create([
                'project_key' => 'default',
                'name' => 'デフォルト店舗',
                'description' => 'デモ用プロジェクト',
                'is_active' => true,
            ]);
        }

        $seeder = app(DefaultDataSeeder::class);
        $seeder->runForProject($project, false, false);

        $this->call(AllSampleDataSeeder::class);

        if (app()->environment('local')) {
            $this->call(DemoPreviewSeeder::class);
        }
    }
}
