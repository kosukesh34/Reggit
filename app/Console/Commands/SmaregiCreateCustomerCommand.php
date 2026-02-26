<?php

namespace App\Console\Commands;

use App\Services\Smaregi\SmaregiApiService;
use Illuminate\Console\Command;

class SmaregiCreateCustomerCommand extends Command
{
    protected $signature = 'smaregi:create-customer
                            {--project_id=1 : プロジェクトID}
                            {--code= : 会員コード（未指定時は TEST_YYYYMMDDHHIISS を自動生成）}
                            {--first_name= : 名（必須）}
                            {--last_name= : 姓（必須）}
                            {--first_kana= : 名カナ（会員必須項目で必須の場合は指定）}
                            {--last_kana= : 姓カナ（会員必須項目で必須の場合は指定）}
                            {--email= : メールアドレス}
                            {--phone= : 電話番号}';

    protected $description = 'スマレジに会員を1件登録する（疎通確認用）';

    public function handle(SmaregiApiService $smaregiApiService): int
    {
        $projectId = (int) $this->option('project_id');
        $contractId = $smaregiApiService->getContractIdForProject($projectId);

        if ($contractId === null || $contractId === '') {
            $this->error("プロジェクト ID {$projectId} に紐づくスマレジ契約がありません。管理画面の「スマレジ連携」で契約を紐付けてください。");
            return self::FAILURE;
        }

        $code = $this->option('code');
        if ($code === null || $code === '') {
            $code = 'TEST_' . date('YmdHis');
        }

        $firstName = $this->option('first_name');
        $lastName = $this->option('last_name');
        if ($firstName === null || $firstName === '' || $lastName === null || $lastName === '') {
            $this->warn('--first_name と --last_name は必須です。未指定のためサンプル名で登録します。');
            $firstName = $firstName !== null && $firstName !== '' ? $firstName : '確認';
            $lastName = $lastName !== null && $lastName !== '' ? $lastName : 'テスト';
        }

        $firstKana = $this->option('first_kana');
        $lastKana = $this->option('last_kana');
        if ($firstKana === null || $firstKana === '') {
            $firstKana = $firstName;
        }
        if ($lastKana === null || $lastKana === '') {
            $lastKana = $lastName;
        }

        $body = [
            'customerCode' => $code,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'firstKana' => $firstKana,
            'lastKana' => $lastKana,
        ];

        $email = $this->option('email');
        if ($email !== null && $email !== '') {
            $body['mailAddress'] = $email;
        }

        $phone = $this->option('phone');
        if ($phone !== null && $phone !== '') {
            $body['phoneNumber'] = $phone;
        }

        $token = $smaregiApiService->getAppAccessToken($contractId);
        if ($token === null || $token === '') {
            $this->error('アクセストークンを取得できませんでした。');
            $this->line('  .env に SMAREGI_CLIENT_ID と SMAREGI_CLIENT_SECRET を設定し、Docker を再起動（docker compose up -d --force-recreate app）してから再度実行してください。');
            return self::FAILURE;
        }

        $this->info("契約ID: {$contractId} に会員を登録します...");
        $result = $smaregiApiService->createCustomer($contractId, $body);

        if ($result === null) {
            $this->error('登録に失敗しました。');
            $this->line('  storage/logs/laravel.log の [Smaregi] API request failed の response_body にスマレジからのエラー内容が記録されています。');
            $this->line('  スマレジ・デベロッパーズで pos.customers:write スコープが有効か確認してください。');
            return self::FAILURE;
        }

        $customerId = $result['customerId'] ?? null;
        $this->info('登録しました。');
        $this->table(
            ['項目', '値'],
            [
                ['会員ID（customerId）', $customerId !== null ? $customerId : '—'],
                ['会員コード', $result['customerCode'] ?? '—'],
                ['姓', $result['lastName'] ?? '—'],
                ['名', $result['firstName'] ?? '—'],
            ]
        );
        $this->newLine();
        $this->comment('登録の確認: php artisan smaregi:list-customers --project_id=' . $projectId . ' で会員一覧を表示できます。');
        $this->comment('※ 開発環境（api.smaregi.dev）で登録した会員はサンドボックス契約のみに存在し、本番のスマレジ店舗管理画面の会員一覧には表示されません。');

        return self::SUCCESS;
    }
}
