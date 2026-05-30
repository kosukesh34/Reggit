<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\Project;
use App\Services\Smaregi\SmaregiApiService;
use Illuminate\Console\Command;

class SmaregiApiDoctorCommand extends Command
{
    protected $signature = 'smaregi:api-doctor
                            {--project_id= : プロジェクトID（未指定時は default）}
                            {--member_id= : ローカル会員ID（smaregi_customer_id で疎通確認）}
                            {--customer_id= : スマレジ会員ID（member_id より優先）}';

    protected $description = 'スマレジ API 疎通診断（トークン・店舗・会員・ポイント取得）';

    public function handle(SmaregiApiService $smaregiApi): int
    {
        $clientId = (string) config('smaregi.client_id', '');
        $clientSecret = (string) config('smaregi.client_secret', '');
        $idBase = (string) config('smaregi.id_base_url', '');
        $apiBase = (string) config('smaregi.api_base_url', '');

        $this->info('=== スマレジ API 疎通診断 ===');
        $this->table(['項目', '値'], [
            ['SMAREGI_CLIENT_ID', $clientId !== '' ? '設定済み' : '未設定'],
            ['SMAREGI_CLIENT_SECRET', $clientSecret !== '' ? '設定済み' : '未設定'],
            ['SMAREGI_ID_BASE_URL', $idBase !== '' ? $idBase : '(未設定)'],
            ['SMAREGI_API_BASE_URL', $apiBase !== '' ? $apiBase : '(未設定)'],
            ['SMAREGI_CONTRACT_ID (config)', (string) config('smaregi.contract_id', '')],
        ]);

        if ($clientId === '' || $clientSecret === '') {
            $this->error('SMAREGI_CLIENT_ID / SMAREGI_CLIENT_SECRET を .env に設定してください。');

            return self::FAILURE;
        }

        $project = $this->resolveProject();
        if ($project === null) {
            $this->error('プロジェクトが見つかりません。');

            return self::FAILURE;
        }

        $contractId = $smaregiApi->getContractIdForProject((int) $project->id);
        if ($contractId === null || $contractId === '') {
            $this->error('契約IDが解決できません。管理画面のスマレジ連携または SMAREGI_CONTRACT_ID を確認してください。');

            return self::FAILURE;
        }

        $this->info("プロジェクト: {$project->name} (ID: {$project->id})");
        $this->info("契約ID: {$contractId}");

        $token = $smaregiApi->getAppAccessToken($contractId);
        if ($token === null) {
            $this->error('アクセストークン取得に失敗しました。契約ID・クライアント認証情報・ID Base URL を確認してください。');

            return self::FAILURE;
        }
        $this->info('アクセストークン: 取得成功');

        $stores = $smaregiApi->getStores($contractId);
        if ($stores === null) {
            $this->error('店舗一覧取得に失敗: '.$this->formatApiError($smaregiApi));

            return self::FAILURE;
        }
        $this->info('店舗一覧: 取得成功');

        $customerId = $this->resolveSmaregiCustomerId($smaregiApi, $contractId, $project);
        if ($customerId === null) {
            $this->warn('会員ID未指定のため会員・ポイント取得はスキップ（--member_id または --customer_id）');

            return self::SUCCESS;
        }

        $this->info("スマレジ会員ID: {$customerId}");

        $customer = $smaregiApi->getCustomer($contractId, $customerId);
        if ($customer === null) {
            $this->error('会員取得に失敗: '.$this->formatApiError($smaregiApi));

            return self::FAILURE;
        }
        $this->info('会員取得: 成功 (customerCode='.($customer['customerCode'] ?? '—').')');

        $point = $smaregiApi->getCustomerPoint($contractId, $customerId);
        if ($point === null) {
            $this->error('ポイント取得に失敗: '.$this->formatApiError($smaregiApi));

            return self::FAILURE;
        }

        $balance = '(不明)';
        foreach (['point', 'points', 'Point', 'Points'] as $pKey) {
            if (array_key_exists($pKey, $point)) {
                $balance = $point[$pKey] ?? 0;
                break;
            }
        }
        $this->info('ポイント取得: 成功 (balance='.$balance.')');
        if ($this->getOutput()->isVerbose()) {
            $this->line('ポイントレスポンス: '.json_encode($point, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        return self::SUCCESS;
    }

    private function resolveProject(): ?Project
    {
        $projectId = $this->option('project_id');
        if ($projectId !== null && $projectId !== '') {
            return Project::find((int) $projectId);
        }

        return Project::where('project_key', 'default')->where('is_active', true)->first()
            ?? Project::where('is_active', true)->orderBy('id')->first();
    }

    private function resolveSmaregiCustomerId(SmaregiApiService $smaregiApi, string $contractId, Project $project): ?string
    {
        $explicit = trim((string) ($this->option('customer_id') ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        $memberId = $this->option('member_id');
        if ($memberId === null || $memberId === '') {
            return null;
        }

        $member = Member::query()
            ->where('project_id', $project->id)
            ->whereKey((int) $memberId)
            ->first();
        if ($member === null) {
            $this->warn("会員 #{$memberId} が見つかりません。");

            return null;
        }

        $linked = trim((string) ($member->smaregi_customer_id ?? ''));
        if ($linked !== '') {
            return $linked;
        }

        $memberNumber = trim((string) ($member->member_number ?? ''));
        if ($memberNumber === '') {
            return null;
        }

        $remote = $smaregiApi->getCustomerByCode($contractId, $memberNumber);
        $resolved = $remote['customerId'] ?? $remote['customer_id'] ?? null;

        return $resolved !== null ? (string) $resolved : null;
    }

    private function formatApiError(SmaregiApiService $smaregiApi): string
    {
        $detail = $smaregiApi->getLastErrorDetail();

        return $detail !== null && $detail !== '' ? $detail : '詳細は storage/logs の [Smaregi] を参照';
    }
}
