# スマレジ・プラットフォームAPI 連携 セットアップ

## 1. 環境変数（.env）

以下を設定してください。スマレジ・デベロッパーズの「環境設定」で確認できます。

```env
SMAREGI_CLIENT_ID=84cfca5d1e33f9f027e30b2f019db903
SMAREGI_CLIENT_SECRET=fd9690fc640f616cb17c2d4c7ab00635e188d6f91efbb6cfb9564e6977225318
```

- **開発環境（サンドボックス）**（デフォルト）  
  `SMAREGI_ID_BASE_URL` / `SMAREGI_API_BASE_URL` は未設定でOK（`id.smaregi.dev` / `api.smaregi.dev` が使われます）。

- **本番環境**にする場合  
  ```env
  SMAREGI_ID_BASE_URL=https://id.smaregi.jp
  SMAREGI_API_BASE_URL=https://api.smaregi.jp
  ```

- **アプリのベースURL**  
  ngrok などで公開しているURLを `APP_URL` に設定してください。  
  例: `APP_URL=https://unadministrable-idiocratically-annalee.ngrok-free.dev`  
  これが「スマレジ・デベロッパーズ」で設定する「アプリURL」のベースになります。

## 2. スマレジ・デベロッパーズで設定するURL

| 項目 | 設定するURL |
|------|-------------|
| **アプリURL（必須）** | ユーザーがアプリを利用するURL（例: 上記 ngrok のURL や `https://あなたのドメイン`） |
| **利用者契約通知先URL（必須）** | `{APP_URL}/api/smaregi/idp` |
| **Webhook送信先エンドポイント** | `{APP_URL}/api/smaregi/webhook`（任意） |

例（ngrok の場合）:

- 利用者契約通知先URL: `https://unadministrable-idiocratically-annalee.ngrok-free.dev/api/smaregi/idp`
- Webhook送信先: `https://unadministrable-idiocratically-annalee.ngrok-free.dev/api/smaregi/webhook`

リダイレクトURIは認可コードフロー用です。アプリアクセストークン（Client Credentials）のみ使う場合は `urn:ietf:wg:oauth:2.0:oob` のままで問題ありません。

## 3. マイグレーション

```bash
php artisan migrate
```

`smaregi_contracts` テーブルが作成されます。

## 4. 管理画面での紐付け

1. 管理画面 → **設定** → **スマレジ連携** を開く。
2. 画面上部に表示される **利用者契約通知先URL** と **Webhook送信先** を、スマレジ・デベロッパーズの該当項目にコピーして保存。
3. スマレジ側でアプリを利用開始（またはお試し利用）すると、契約通知が送られ、**受信した契約一覧** に契約IDが表示されます。
4. **このプロジェクトに紐づける契約** で対象の契約を選び **保存**。

## 5. API の利用例（スマレジから取得）

プロジェクトに契約を紐づけたあと、コードからは次のように利用できます。

```php
use App\Services\Smaregi\SmaregiApiService;

$service = app(SmaregiApiService::class);
$contractId = $service->getContractIdForProject($projectId);
if ($contractId !== null) {
    $stores = $service->getStores($contractId);
    $products = $service->getProducts($contractId);
    $customers = $service->getCustomers($contractId, ['limit' => 100]);
}
```

## 6. こちらからスマレジへ送信する

こちらのアプリで発生した内容をスマレジに送る場合は、以下のAPIを使用します。

### 会員ポイントの加算・減算

スマレジの会員に対してポイント（およびマイル）を加算・減算します。負の値を指定すると減算になります。

**スマレジ・デベロッパーズの「スコープ」で `pos.customers:write` を有効にしてください。**

```php
use App\Services\Smaregi\SmaregiApiService;

$service = app(SmaregiApiService::class);
$contractId = $service->getContractIdForProject($projectId);
if ($contractId !== null) {
    // スマレジ会員ID（会員一覧取得で取得した customerId）を指定
    $result = $service->addCustomerPoints(
        $contractId,
        '1',           // スマレジの会員ID
        10,            // 加算するポイント（負の値で減算）
        null,          // マイル（任意）
        '2026-12-31'   // ポイント失効予定日 Y-m-d（任意）
    );
    // $result に customerId, point, transactionHeadIds などが含まれる
}
```

プロジェクトIDだけ分かっていて、契約IDの解決を任せたい場合は UseCase を使います。

```php
use App\UseCases\Smaregi\SmaregiSyncCustomerPointsUseCase;

$useCase = app(SmaregiSyncCustomerPointsUseCase::class);
$result = $useCase->execute(
    $projectId,
    '1',    // スマレジ会員ID
    10,     // 加算ポイント（負で減算）
    null,   // マイル（任意）
    null    // ポイント失効予定日（任意）
);
if ($result !== null) {
    // 送信成功。$result['transactionHeadIds'] に取引IDが入る
}
```

会員IDの取得は、`$service->getCustomers($contractId)` で会員一覧を取得し、会員コードや電話番号などで突き合わせてください。

### 会員作成の疎通確認（コマンドでテスト）

スマレジに会員が登録されることを確認するには、次の Artisan コマンドを実行します。

1. **前提**: 管理画面の「スマレジ連携」で、対象プロジェクトに契約を紐付けておく。
2. **実行例**（Docker 内）:
   ```bash
   docker compose exec app php artisan smaregi:create-customer --project_id=1
   ```
   オプションで会員コード・氏名・メール・電話を指定できます:
   ```bash
   php artisan smaregi:create-customer --project_id=1 --code=MEM001 --first_name=花子 --last_name=佐藤 --email=sato@example.com --phone=03012345678
   ```
3. **成功時**: コマンドが「登録しました」と表示し、会員ID・会員コードが出力されます。
4. **登録の確認**:
   - 同じ契約の会員一覧を表示: `php artisan smaregi:list-customers --project_id=1`
   - **開発環境（api.smaregi.dev）で登録した会員はサンドボックス契約のみに存在します。** 本番のスマレジ店舗管理画面（店舗向けバックオフィス）の会員一覧には表示されません。登録確認は上記コマンドや API（GET pos/customers）で行ってください。
   - 本番環境（api.smaregi.jp）に切り替えている場合は、その契約に紐づくスマレジ店舗の管理画面で会員が確認できます。

失敗する場合は、`.env` の `SMAREGI_CLIENT_ID` / `SMAREGI_CLIENT_SECRET`、スマレジ・デベロッパーズのスコープ（`pos.customers:write`）、および契約の紐付けを確認してください。

必要なスコープは `.env` の `SMAREGI_SCOPE` で指定します。デフォルトで会員・商品・取引・店舗・在庫・スタッフ等の読み書きスコープを含めています。スマレジ・デベロッパーズの「スコープ」で利用したい機能を有効にしてください。

**POS API のその他エンドポイント**（会員作成・取引登録・部門・セール・在庫・精算・クーポンなど）は、`$service->request($contractId, $method, $path, $params)` で任意の path を指定して呼び出せます。全パス一覧は [docs/SMAREGI_POS_API_REFERENCE.md](SMAREGI_POS_API_REFERENCE.md) を参照してください。

## 7. Webhook の検証（任意）

Webhook でカスタムヘッダーにシークレットを設定している場合:

```env
SMAREGI_WEBHOOK_SECRET=あなたが設定したシークレット文字列
SMAREGI_WEBHOOK_SECRET_HEADER=X-Smaregi-Webhook-Secret
```

スマレジの Webhook 設定で付与したヘッダー名と `SMAREGI_WEBHOOK_SECRET_HEADER` を一致させ、値に `SMAREGI_WEBHOOK_SECRET` を設定してください。

## 8. 実接続のテスト（任意）

モックではなく、**現状の .env と紐付いた契約**を使って実際のスマレジAPIへ接続する統合テストがあります。

**利用する情報**

- `.env` の `SMAREGI_CLIENT_ID` / `SMAREGI_CLIENT_SECRET` / `SMAREGI_ID_BASE_URL` / `SMAREGI_API_BASE_URL`
- 契約ID: 次の順で参照します。(1) `SMAREGI_TEST_CONTRACT_ID` (2) `SMAREGI_CONTRACT_ID` (3) プロジェクト1に紐づいているスマレジ契約（DB）。**テスト実行時はテスト用DBが使われるため、.env に `SMAREGI_CONTRACT_ID=sb_xxxxxxxx` を入れておくと確実です。**

**実行方法**

```bash
docker compose exec app php artisan test tests/Feature/SmaregiApiIntegrationTest.php
# または
docker compose exec app php artisan test --group=smaregi-integration
```

資格情報や契約が無い環境では各テストはスキップされます。
