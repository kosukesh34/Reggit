<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\Smaregi\SmaregiProductRegisterService;
use App\Services\Smaregi\SmaregiProductSyncService;
use Database\Seeders\DefaultDataSeeder;
use Illuminate\Console\Command;

class SeedDefaultDataCommand extends Command
{
    protected $signature = 'seed:default-data
                            {--project_id= : プロジェクトID（未指定時は project_key=default のプロジェクト）}
                            {--force : 既存の商品・クーポンを削除してからデフォルトデータを投入する}
                            {--no-smaregi : スマレジへの登録をスキップする}';

    protected $description = 'デフォルトの商品・クーポンデータをプロジェクトに投入し、スマレジ連携時はスマレジへも登録する';

    public function handle(
        DefaultDataSeeder $seeder,
        SmaregiProductRegisterService $registerService,
        SmaregiProductSyncService $syncService,
    ): int {
        $projectId = $this->option('project_id');
        $project = $projectId !== null && $projectId !== ''
            ? Project::find((int) $projectId)
            : Project::where('project_key', 'default')->where('is_active', true)->first();

        if ($project === null) {
            $this->error('対象のプロジェクトが見つかりません。--project_id= を指定するか、project_key=default のアクティブなプロジェクトを用意してください。');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        if ($force) {
            if (! $this->confirm("プロジェクト「{$project->name}」(ID: {$project->id}) の既存の商品・クーポンを削除してデフォルトデータで上書きします。よろしいですか？", false)) {
                $this->info('中止しました。');

                return self::SUCCESS;
            }
        }

        $this->info("プロジェクト: {$project->name} (ID: {$project->id}) にデフォルトデータを投入します。");
        $seeder->runForProject($project, $force, $force);
        $this->info('デフォルトの商品・クーポンの投入が完了しました。');

        $skipSmaregi = (bool) $this->option('no-smaregi');
        if (! $skipSmaregi && $registerService->hasSmaregiContract($project->id)) {
            $this->info('スマレジへ商品を同期しています...');
            try {
                $result = $syncService->fullSync($project->id);
                $this->info(sprintf(
                    'スマレジ同期完了: 作成=%d, 更新=%d, スマレジへ登録=%d, 失敗=%d',
                    $result['created'],
                    $result['updated'],
                    $result['pushed_to_smaregi'],
                    $result['failed']
                ));
                if ($result['errors'] !== []) {
                    foreach ($result['errors'] as $err) {
                        $this->warn("  - {$err}");
                    }
                }
            } catch (\Throwable $e) {
                $this->error('スマレジ同期に失敗しました: '.$e->getMessage());

                return self::FAILURE;
            }
        } elseif ($skipSmaregi) {
            $this->comment('--no-smaregi のためスマレジへの登録はスキップしました。');
        } else {
            $this->comment('スマレジ契約が紐づいていないため、スマレジへの登録は行いません。');
        }

        return self::SUCCESS;
    }
}
