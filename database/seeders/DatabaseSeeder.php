<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Project;
use App\Services\Smaregi\SmaregiProductRegisterService;
use App\Services\Smaregi\SmaregiProductSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    use Concerns\UpsertsAdminUsers;

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

        // 会員・注文などのサンプルデータは DEMO_SEED=true のときだけ投入（再 seed で既存データを壊さない）
        if (self::isDemoSeedEnabled()) {
            $this->call(AllSampleDataSeeder::class);
        }

        // スマレジ契約が紐づいている場合は、投入した商品をスマレジにも登録する
        $registerService = app(SmaregiProductRegisterService::class);
        $syncService = app(SmaregiProductSyncService::class);
        $needsSmaregiSync = Product::withoutGlobalScopes()
            ->where('project_id', $project->id)
            ->whereNull('smaregi_product_id')
            ->exists();

        if ($registerService->hasSmaregiContract($project->id) && $needsSmaregiSync) {
            try {
                $result = $syncService->fullSync($project->id);
                Log::info('[Seeder] スマレジへデフォルト商品を同期しました', [
                    'project_id' => $project->id,
                    'created' => $result['created'],
                    'updated' => $result['updated'],
                    'pushed_to_smaregi' => $result['pushed_to_smaregi'],
                    'failed' => $result['failed'],
                    'errors' => $result['errors'],
                ]);
            } catch (\Throwable $e) {
                Log::warning('[Seeder] スマレジ同期に失敗しました（デフォルトデータはDBには投入済み）', [
                    'project_id' => $project->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }
}
