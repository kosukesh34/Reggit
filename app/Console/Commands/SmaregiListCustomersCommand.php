<?php

namespace App\Console\Commands;

use App\Services\Smaregi\SmaregiApiService;
use Illuminate\Console\Command;

class SmaregiListCustomersCommand extends Command
{
    protected $signature = 'smaregi:list-customers
                            {--project_id=1 : プロジェクトID}
                            {--limit=50 : 取得件数}
                            {--code= : 会員コードで絞り込み}';

    protected $description = '紐付いたスマレジ契約の会員一覧をAPIで取得して表示する（疎通・登録確認用）';

    public function handle(SmaregiApiService $smaregiApiService): int
    {
        $projectId = (int) $this->option('project_id');
        $contractId = $smaregiApiService->getContractIdForProject($projectId);

        if ($contractId === null || $contractId === '') {
            $this->error("プロジェクト ID {$projectId} に紐づくスマレジ契約がありません。管理画面の「スマレジ連携」で契約を紐付けてください。");

            return self::FAILURE;
        }

        $query = ['limit' => (int) $this->option('limit')];
        $code = $this->option('code');
        if ($code !== null && $code !== '') {
            $query['customer_code'] = $code;
        }

        $this->info("契約ID: {$contractId} の会員一覧を取得しています...");
        $result = $smaregiApiService->getCustomers($contractId, $query);

        if ($result === null) {
            $this->error('会員一覧の取得に失敗しました。');
            $this->line('  storage/logs/laravel.log の [Smaregi] API request failed を確認してください。');
            $this->line('  スマレジ・デベロッパーズで pos.customers:read スコープが有効か確認してください。');

            return self::FAILURE;
        }

        $customers = $result['customers'] ?? $result;
        if (! is_array($customers)) {
            $customers = [];
        }

        $total = $result['total'] ?? count($customers);
        $totalLabel = isset($result['total']) ? " / 総数: {$total}" : '';
        $this->info('会員数: '.count($customers).$totalLabel);
        $this->newLine();

        if (count($customers) === 0) {
            $this->comment('会員が0件です。smaregi:create-customer で登録してから再度お試しください。');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($customers as $c) {
            $rows[] = [
                $c['customerId'] ?? '—',
                $c['customerCode'] ?? '—',
                ($c['lastName'] ?? '').' '.($c['firstName'] ?? ''),
                $c['mailAddress'] ?? '—',
            ];
        }
        $this->table(['会員ID', '会員コード', '氏名', 'メール'], $rows);

        $this->newLine();
        $this->comment('※ 開発環境（api.smaregi.dev）で取得している場合、この会員はサンドボックス契約のみに存在します。');
        $this->comment('  本番のスマレジ店舗管理画面の会員一覧には表示されません。上記のAPI結果で登録を確認してください。');

        return self::SUCCESS;
    }
}
