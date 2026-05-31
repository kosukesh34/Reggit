# スマレジ Webhook 受信確認手順

## 1. 送信先URLの確認

ngrok 起動後、APP_URL を同期して正しい Webhook URL を表示します:

```bash
./start-ngrok.sh
# または
make ngrok-sync
```

表示された **Webhook URL** をスマレジ・デベロッパーズの「Webhook送信先エンドポイント」にそのまま登録してください。

- パスは `/api/smaregi/webhook` です（`smarec` ではなく `smaregi`）
- 末尾に `/webhook` を含めます
- ngrok の URL は再起動のたびに変わるため、**変わったら必ずスマレジ側も更新**してください

## 2. 到達性の事前確認（任意）

```bash
make smaregi-webhook-probe
```

成功するとログに `[Smaregi] Webhook ingress（リクエスト到達）` が出ます。

## 3. テスト通知送信

スマレジ画面で「テスト通知を送信」ボタンを押すと、上記URLへ POST が送られます。

## 4. 受信ログの確認

Docker 内でログを追いかける場合:
```bash
docker compose exec app tail -f storage/logs/laravel.log
```

受信に成功していると、次のような行が出ます（すべて `[Smaregi]` で始まります）:
```
[日時] local.INFO: [Smaregi] Webhook HTTP request accepted (受信) {"smaregi_contract_id":"...","event":"...","event_resolved_from":"header_smaregi-event","action":"...","json_body_keys":[...]}
```

- **`Webhook HTTP request accepted (受信)`** が出ていれば、HTTP として Webhook エンドポイントまで届いています。
- 会員イベント（`pos:customers`）の場合は続けて **`Webhook pos:customers ポイント同期ジョブをレスポンス送信後に実行予定`** が出ます。
- 同期処理が走ると **`Inbound point sync job started（Webhook 後処理開始）`** → **`Inbound point sync job finished（Webhook 後処理完了）`** が出ます（ポイントに差分がなければ「更新なし」のログのみのこともあります）。

絞り込み例:
```bash
docker compose exec app grep '\[Smaregi\]' storage/logs/laravel.log | tail -n 50
```

- 400/401 の場合は、`[Smaregi] Webhook missing event` や `[Smaregi] Webhook secret mismatch` など別のメッセージが出ます。

受信の最初の印は **`[Smaregi] Webhook ingress（リクエスト到達）`** です。これが無い場合はスマレジ→ngrok→アプリのどこかで届いていません。

## 5. カスタムヘッダー（シークレット）を設定している場合

.env で `SMAREGI_WEBHOOK_SECRET` を設定している場合、スマレジの Webhook 設定で同じ値を持つカスタムヘッダーを付与する必要があります。  
テストのみなら、いったん `SMAREGI_WEBHOOK_SECRET` を空にして受信確認しても構いません。
