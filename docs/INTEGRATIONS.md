# 外部連携

必要な連携だけを有効にします。設定名と既定値は `.env.example` と `config/`、本番の共通設定は [デプロイ](DEPLOY.md) を参照します。認証情報・秘密鍵・署名付きURLは文書やログに貼り付けません。

## LINE

`LINE_CHANNEL_ACCESS_TOKEN`、`LINE_CHANNEL_SECRET`、`LINE_LIFF_ID` を設定します。プロジェクト別の LINE / LIFF 設定がある場合は管理画面の値を使います。LIFF のエンドポイントは `{APP_URL}/liff/{project_key}`、Webhook は `{APP_URL}/api/line/webhook` です。

本番の顧客本人確認は `X-Line-Id-Token` を検証して行います。LINE のユーザーIDを送るだけでは認証できません。署名・本人確認の実装は `LineIdTokenVerifier`、`LineIdentityPolicy`、LINE Webhook 関連 Service を参照します。

## スマレジ

### 設定と契約

管理画面の「設定 → スマレジ連携」で Client ID / Secret と契約を設定します。公開オンボーディングでもプロジェクト別の認証情報を登録でき、作成管理者は `project_admin` です。共通の `SMAREGI_CLIENT_ID` / `SMAREGI_CLIENT_SECRET` はプロジェクトに設定がない場合のフォールバックです。

| 用途 | 設定 |
|---|---|
| 接続先 | `SMAREGI_ID_BASE_URL` / `SMAREGI_API_BASE_URL` |
| 本番接続先 | `https://id.smaregi.jp` / `https://api.smaregi.jp` |
| 開発接続先 | `https://id.smaregi.dev` / `https://api.smaregi.dev` |
| 利用スコープ | `SMAREGI_SCOPE`。アプリ側とスマレジ側の許可を揃える |
| 契約通知 | `SMAREGI_IDP_CALLBACK_TOKEN` |
| POS Webhook | `SMAREGI_WEBHOOK_SECRET` / `SMAREGI_WEBHOOK_SECRET_HEADER` |

スマレジ・デベロッパーズに登録する URL は、管理画面に表示された値を使用します。

| 項目 | URL |
|---|---|
| アプリ | `{APP_URL}/onboarding` |
| 利用者契約通知 | `{APP_URL}/api/smaregi/idp?callback_token={SMAREGI_IDP_CALLBACK_TOKEN}` |
| POS Webhook | `{APP_URL}/api/smaregi/webhook` |

契約通知トークンは環境ごとの64桁ランダム16進値です。未設定・不一致は拒否します。POS Webhook のカスタムヘッダーは契約通知には送られません。POS Webhook は設定したヘッダー名と秘密値を送信側にも登録し、本番では未設定のまま受信しません。

利用開始通知を受け取ったら、受信契約一覧から対象プロジェクトへ契約を紐づけます。開発契約と本番契約は別です。ngrok の URL が変わった場合は `APP_URL`、LIFF、スマレジの通知先を更新します。

### 同期と確認

`SmaregiApiService` が契約解決・トークン取得・POS API 通信を担当します。会員、商品、店舗、取引、クーポンなどの同期は `app/Services/Smaregi/` とイベント / Job を参照します。店舗対応はローカル `Store` の外部店舗IDを使い、複数店舗で対応が不明な受信データを別店舗へ流用しません。

会員ポイントの同期は `syncCustomerPointsForProject` など既存の経路を利用し、会員書き込みにはスマレジ側の `pos.customers:write` が必要です。項目・型・全エンドポイントの複製は置かず、[公式 API リファレンス](https://developers.smaregi.dev/platform-api-reference/) を参照します。

Webhook の確認は開発契約でテスト通知を送り、`storage/logs/laravel.log` の `[Smaregi]` を確認します。「HTTP request accepted」は受信、「Inbound point sync job started / finished」は後処理の実行を示します。受信だけで同期完了とは判断しません。400 / 401 はイベント・契約・秘密値、到達しない場合は通知先とトンネルを確認します。

```bash
php artisan test --group=smaregi-integration
```

実接続テストには有効な資格情報とテスト契約が必要です。契約は `SMAREGI_TEST_CONTRACT_ID` / `SMAREGI_CONTRACT_ID` などから解決します。テスト用 DB に切り替わるため、開発 DB の紐付けだけに依存しません。通常テストからの除外方法は [開発手順](DEVELOPMENT.md#テストと静的検査) に記載しています。

## Stripe

`STRIPE_KEY`、`STRIPE_SECRET`、`STRIPE_WEBHOOK_SECRET` を設定し、署名検証する受信先 `{APP_URL}/api/payment/webhook` を登録します。開発と本番のキー・Webhook を混ぜません。

Intent / Checkout の作成・確認は `app/UseCases/Payment/` を使います。注文所有者・metadata の注文ID・金額・状態を照合し、決済セッションと冪等キーを再利用します。ローカル保存の完了と外部同期の完了を区別します。

## Wallet

### Google Wallet

1. Google Pay & Wallet Console の Issuer とサービスアカウントを用意する。
2. サービスアカウント秘密鍵 JSON を `storage/app/google_wallet/credentials.json` に配置する。Git には含めない。
3. Console のアプリ権限へ、実際にボタンを表示するオリジンを登録する。
4. `GOOGLE_WALLET_ENABLED=true`、`GOOGLE_WALLET_ISSUER_ID`、`GOOGLE_WALLET_ORIGINS` を設定する。

認証ファイルは `GOOGLE_WALLET_CREDENTIALS_PATH` で変更できます。オリジンはスキーム・ホスト・ポートまででパスを含めず、Console と揃えます。カード識別子・画像・背景色は `config/google_wallet.php` を参照します。本番では `GOOGLE_WALLET_ALLOW_DEMO_USER=false` にします。

登録済み会員の画面から追加し、Issuer・署名鍵・オリジンを確認します。追加済みカードと新規発行カードの表示更新は区別します。API の本人確認は通常の会員認証を使用し、URL クエリへ認証トークンを追加しません。

公式: [Loyalty](https://developers.google.com/wallet/retail/loyalty-cards/overview)、[JWT 保存リンク](https://developers.google.com/wallet/retail/loyalty-cards/use-cases/jwt)。

### Apple Wallet

会員証パスの有効化、Pass Type ID、Team ID、証明書・秘密鍵・WWDR 証明書は `WALLET_PASS_*` と `config/wallet_pass.php` に従って設定します。秘密鍵を公開ストレージへ置きません。会員証画面で `.pkpass` の追加と端末登録を確認します。Web Service は `/api/apple-wallet/v1/...` です。

## 日本郵便

`JAPANPOST_API_HOST`、`JAPANPOST_CLIENT_ID`、`JAPANPOST_CLIENT_SECRET` を設定します。スタブと本番では接続先・資格情報を揃えます。

IP 制限がある場合に登録するのは、Laravel サーバーの外向きIPです。ngrok の公開ホストIPとは異なります。403 は資格情報、利用契約、送信元IPの順に確認します。疎通用スクリプトは `scripts/test-japanpost-api.sh` です。

公式: [郵便番号・デジタルアドレス for Biz](https://guide-biz.da.pf.japanpost.jp/)。

## Push・メール・リアルタイム

- チャット（Firestore / Storage）: Firebase コンソールで Firestore を有効にし、Web アプリを登録して `FIREBASE_WEB_API_KEY`・`FIREBASE_WEB_APP_ID` を設定する。画像を使う場合だけ Storage（Blaze プランが必要）を有効にして `FIREBASE_STORAGE_BUCKET` を設定する（空なら画像なし）。プロジェクトIDとサービスアカウントは未設定なら `PUSH_FCM_*` を使う。`firebase deploy --only firestore:rules,firestore:indexes` でルールとインデックスを反映する。Storage を使う場合は `storage` も反映し、Storage ルールが Firestore を参照するための初回の権限付与を許可し、バケットの CORS で管理画面・LIFF のオリジンからの `GET` を許可する。ルールの検証は `npm run test:firebase-rules`（Java 21 以上が必要）。
- FCM: `PUSH_FCM_ENABLED`、`PUSH_FCM_PROJECT_ID`、`PUSH_FCM_CREDENTIALS` を設定する。顧客アプリの iOS / Android 設定は [Skip README](../mobile/reggit-skip/README.md) を参照する。送信受付と端末受信を別に検証する。
- メール: `MAIL_*` を実際の SMTP 設定に合わせる。送信元、パスワードリセット、注文通知を確認する。
- Reverb: `BROADCAST_CONNECTION` / `BROADCAST_DRIVER`、`REVERB_*` を合わせ、Reverb プロセスと WebSocket プロキシを用意する。署名用 Secret は公開しない。

## 分析・配信 MCP

`scripts/analysis-mcp.mjs` は Node.js の標準入出力 MCP サーバーです。クライアント側に次を設定します。

```json
{
  "mcpServers": {
    "reggit-analysis": {
      "command": "node",
      "args": ["/absolute/path/to/Reggit/scripts/analysis-mcp.mjs"],
      "env": {
        "REGGIT_BASE_URL": "http://localhost:8080",
        "REGGIT_PROJECT_KEY": "対象プロジェクトキー",
        "REGGIT_API_TOKEN": "発行した分析専用トークン"
      }
    }
  }
}
```

管理画面で外部AIの分析API利用を許可し、90日期限の分析専用トークンを発行します。配信は別途許可が必要で、初期状態は両方無効です。設定ファイルを共有・コミットしません。

ツールは `get_analysis_summary`、`list_delivery_coupons`、`deliver_coupon_to_all` です。最後のツールは実配布なので、利用者の明示承認を得てから呼び出します。サーバーに定期実行スケジューラーはありません。
