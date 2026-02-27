<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\GoogleWalletPassService;
use Illuminate\Console\Command;

/**
 * Google Wallet の LoyaltyClass を REST API で作成する。
 * 初回またはクラスが無いときに実行し、Pay & Wallet Console でクラスを承認してから「Add to Google Wallet」を利用する。
 */
class GoogleWalletEnsureClassCommand extends Command
{
    protected $signature = 'google-wallet:ensure-class
                            {--project_key= : プロジェクトキー（省略時は app.name をプログラム名に使用）}';

    protected $description = 'Google Wallet の LoyaltyClass を REST で作成（既存ならスキップ）';

    public function handle(GoogleWalletPassService $wallet): int
    {
        if (! $wallet->isAvailable()) {
            $this->error('Google Wallet が無効か設定不足です。.env の GOOGLE_WALLET_* を確認してください。');
            foreach ($wallet->getUnavailabilityReasons() as $reason) {
                $this->line('  - ' . $reason);
            }

            return self::FAILURE;
        }

        $projectKey = $this->option('project_key');
        $programName = config('app.name', 'Membership');
        if ($projectKey !== null && $projectKey !== '') {
            $project = Project::where('project_key', $projectKey)->where('is_active', true)->first();
            if ($project !== null) {
                $programName = $project->name;
            }
        }

        $issuerId = config('google_wallet.issuer_id');
        $classSuffix = config('google_wallet.class_suffix', 'membership');
        $classId = $issuerId . '.' . $classSuffix;
        $programLogoUri = config('google_wallet.program_logo_uri', 'https://storage.googleapis.com/wallet-lab-tools-codelab-artifacts-public/pass_google_logo.jpg');

        $this->info("Class ID: {$classId}");
        $this->info("Program name: {$programName}");

        if ($wallet->ensureLoyaltyClassExists($classId, $programName, $programLogoUri)) {
            $this->info('LoyaltyClass の作成または存在確認が完了しました。');
            $this->line('Pay & Wallet Console でクラスが UNDER_REVIEW → APPROVED になったら、「Add to Google Wallet」から保存できます。');

            return self::SUCCESS;
        }

        $this->error('LoyaltyClass の作成に失敗しました。');
        $this->line('');
        $this->line('403 エラーの場合: Google Cloud Console で「Google Wallet API」が有効か確認してください。');
        $this->line('  → https://console.cloud.google.com/apis/library で「Google Wallet API」を検索して「有効にする」');
        $this->line('  → または storage/logs/laravel.log の直近の WARNING に有効化用 URL が出力されています。');
        $this->line('');
        $this->line('詳細は storage/logs/laravel.log を確認してください。');

        return self::FAILURE;
    }
}
