<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Services\Smaregi\SmaregiApiService;
use Illuminate\Console\Command;

/**
 * 指定プロジェクトの全会員を削除する（開発・リセット用）。
 * スマレジ連携時はスマレジ側の会員も削除し、API成功を確認してからローカルを削除する。
 * --smaregi-all 指定時は、APIで取得した契約内の全会員をスマレジから削除してからDBをクリアする（POS/DB不一致の完全リセット用）。
 */
class MembersDeleteAllCommand extends Command
{
    private const SMAREGI_PAGE_LIMIT = 100;

    protected $signature = 'members:delete-all
                            {--project_id=1 : プロジェクトID}
                            {--force : 確認なしで実行}
                            {--smaregi-all : スマレジ契約内の全会員をAPIで取得してから削除（POS側も完全リセット）}';

    protected $description = '指定プロジェクトの全会員を削除する（スマレジ連携時はAPI側も削除）';

    public function handle(SmaregiApiService $smaregiApi): int
    {
        $projectId = (int) $this->option('project_id');
        $force = (bool) $this->option('force');
        $smaregiAll = (bool) $this->option('smaregi-all');

        $count = Member::where('project_id', $projectId)->count();
        $contractId = $smaregiApi->getContractIdForProject($projectId) ?? '';

        if ($smaregiAll) {
            $msg = "プロジェクト ID {$projectId} の会員をリセットします。";
            $msg .= ' スマレジ連携時は契約内の全会員をPOS側から削除し、続けてDBの会員をすべて削除します。';
            if ($count > 0) {
                $msg .= " DB会員: {$count} 件。";
            }
            if (! $force && ! $this->confirm($msg.' よろしいですか？', false)) {
                $this->info('中止しました。');

                return self::SUCCESS;
            }

            $smaregiOk = 0;
            $smaregiFail = 0;
            if ($contractId !== '') {
                [$smaregiOk, $smaregiFail] = $this->deleteAllSmaregiCustomers($smaregiApi, $contractId);
            }

            Member::where('project_id', $projectId)->forceDelete();
            $this->info("会員を削除しました。（DB: {$count} 件）");
            if ($contractId !== '') {
                $this->line("  スマレジ削除: 成功 {$smaregiOk} 件".($smaregiFail > 0 ? " / 失敗 {$smaregiFail} 件" : ''));
            }

            return self::SUCCESS;
        }

        if ($count === 0) {
            $this->info("プロジェクト ID {$projectId} の会員は0件です。");

            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm("プロジェクト ID {$projectId} の会員を {$count} 件すべて削除します。よろしいですか？", false)) {
            $this->info('中止しました。');

            return self::SUCCESS;
        }

        $members = Member::where('project_id', $projectId)->get();
        $smaregiOk = 0;
        $smaregiFail = 0;

        foreach ($members as $member) {
            $sid = $member->smaregi_customer_id ?? '';
            if ($sid !== '' && $contractId !== '') {
                $result = $smaregiApi->deleteCustomer($contractId, $sid);
                if ($result !== null) {
                    $smaregiOk++;
                } else {
                    $smaregiFail++;
                    $this->warn("スマレジ会員 {$sid} の削除に失敗しましたが、ローカル会員は削除します。");
                }
            }
            $member->forceDelete();
        }

        $this->info("会員 {$count} 件を削除しました。");
        if ($contractId !== '') {
            $this->line("  スマレジ削除: 成功 {$smaregiOk} 件".($smaregiFail > 0 ? " / 失敗 {$smaregiFail} 件" : ''));
        }

        return self::SUCCESS;
    }

    /**
     * スマレジ契約内の全会員をAPIで取得し、1件ずつ削除する。
     *
     * @return array{int, int} [成功件数, 失敗件数]
     */
    private function deleteAllSmaregiCustomers(SmaregiApiService $smaregiApi, string $contractId): array
    {
        $smaregiOk = 0;
        $smaregiFail = 0;
        $page = 1;

        do {
            $result = $smaregiApi->getCustomers($contractId, [
                'limit' => self::SMAREGI_PAGE_LIMIT,
                'page' => $page,
            ]);
            if ($result === null) {
                $this->warn('スマレジ会員一覧の取得に失敗しました。');
                break;
            }

            $customers = $result['customers'] ?? $result;
            if (! is_array($customers)) {
                $customers = [];
            }

            foreach ($customers as $c) {
                $customerId = $c['customerId'] ?? null;
                if ($customerId === null || $customerId === '') {
                    continue;
                }
                $res = $smaregiApi->deleteCustomer($contractId, (string) $customerId);
                if ($res !== null) {
                    $smaregiOk++;
                } else {
                    $smaregiFail++;
                }
            }

            $page++;
        } while (isset($result['customers']) && is_array($result['customers']) && count($result['customers']) === self::SMAREGI_PAGE_LIMIT);

        return [$smaregiOk, $smaregiFail];
    }
}
