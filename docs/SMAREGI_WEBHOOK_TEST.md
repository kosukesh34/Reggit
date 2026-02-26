# スマレジ Webhook 受信確認手順

## 1. 送信先URLの確認

**正しいURL（必ずこちらにすること）:**
```
https://unadministrable-idiocratically-annalee.ngrok-free.dev/api/smaregi/webhook
```

- パスは `/api/smaregi/webhook` です（`smarec` ではなく `smaregi`）
- 末尾に `/webhook` を含めます

## 2. テスト通知送信

スマレジ画面で「テスト通知を送信」ボタンを押すと、上記URLへ POST が送られます。

## 3. 受信ログの確認

Docker 内でログを追いかける場合:
```bash
docker compose exec app tail -f storage/logs/laravel.log
```

受信に成功していると、次のような行が出ます:
```
[日時] local.INFO: [Smaregi] Webhook received {"smaregi_contract_id":"...","event":"...","action":"..."}
```

- この `[Smaregi] Webhook received` が出ていれば **受信できています**。
- 400/401 の場合は、`[Smaregi] Webhook missing smaregi-event` や `[Smaregi] Webhook secret mismatch` など別のメッセージが出ます。

## 4. カスタムヘッダー（シークレット）を設定している場合

.env で `SMAREGI_WEBHOOK_SECRET` を設定している場合、スマレジの Webhook 設定で同じ値を持つカスタムヘッダーを付与する必要があります。  
テストのみなら、いったん `SMAREGI_WEBHOOK_SECRET` を空にして受信確認しても構いません。
