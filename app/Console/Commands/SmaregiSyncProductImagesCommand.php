<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\Smaregi\SmaregiProductSyncService;
use Illuminate\Console\Command;

class SmaregiSyncProductImagesCommand extends Command
{
    protected $signature = 'smaregi:sync-product-images
                            {--project_id= : プロジェクトID（未指定時はアクティブな全プロジェクト）}';

    protected $description = 'DBの商品画像をスマレジへ登録する';

    public function handle(SmaregiProductSyncService $syncService): int
    {
        $projectId = $this->option('project_id');
        $projects = $projectId !== null && $projectId !== ''
            ? Project::query()->whereKey((int) $projectId)->get()
            : Project::query()->where('is_active', true)->get();

        if ($projects->isEmpty()) {
            $this->error('対象のプロジェクトが見つかりません。');

            return self::FAILURE;
        }

        $hasFailure = false;
        foreach ($projects as $project) {
            $this->info("プロジェクト: {$project->name} (ID: {$project->id})");
            $result = $syncService->syncAllImagesToSmaregi((int) $project->id);
            $this->info(sprintf('  画像反映: 成功=%d, 失敗=%d', $result['synced'], $result['failed']));
            foreach ($result['errors'] as $error) {
                $this->warn("  - {$error}");
            }
            if ($result['failed'] > 0) {
                $hasFailure = true;
            }
        }

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }
}
