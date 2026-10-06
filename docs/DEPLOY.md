# 本番設定とデプロイ

## 本番設定

本番の `.env` はサーバー側で管理します。開発用設定をそのまま転記せず、必要な項目だけを設定します。変数の定義は `.env.example` と `config/`、外部サービス別の設定は [外部連携](INTEGRATIONS.md) を参照してください。

| 項目 | 設定・確認 |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` | HTTPS の本番 URL（現在のサービス URL は `https://reggit.jp`） |
| `APP_KEY` | 初回のみ生成。暗号化データがある運用中環境では変更しない |
| `DB_*` | 本番 DB 名・ホスト・ポート・専用ユーザー・固有パスワード |
| `ADMIN_LOGIN_ID` または `ADMIN_EMAIL` | 初回管理者を作る場合のログインID |
| `ADMIN_PASSWORD` | 初回作成時に固有の値を設定。空値・`reserve` は拒否 |
| `DEMO_SEED` | `false`。本番のデモ管理者作成は禁止 |
| `SESSION_SECURE_COOKIE` | HTTPS 運用では `true` |
| `LOG_LEVEL` | 通常は `warning` / `error`。調査用詳細ログは必要な期間だけ |
| `QUEUE_CONNECTION` | 非同期運用では `database` など。ワーカーを用意する |

seed は既存アカウントのパスワード・ロールを更新しません。既に存在する既定パスワードのアカウントは、この修正だけでは変更されないため運用側で変更します。初期データの seed と通常デプロイを分けます。

## デプロイ

```bash
reggit deploy
# 同等: make deploy
```

実装は [scripts/reggit](../scripts/reggit) です。`reggit` を PATH に置き、変更をコミットし、本番 SSH 鍵を準備します。

| 環境変数 | 用途 |
|---|---|
| `REGGIT_ROOT` | ローカルのプロジェクトルート |
| `REGGIT_DEPLOY_HOST` | SSH 接続先 |
| `REGGIT_DEPLOY_PATH` | 本番アプリディレクトリ |
| `REGGIT_DEPLOY_SSH_KEY` / `REGGIT_DEPLOY_SSH_PORT` | 鍵とポート |
| `REGGIT_DEPLOY_BRANCH` | 反映ブランチ。既定は `main` |

コマンドは反映ブランチを `git pull --ff-only` で更新し、フロントをビルド、rsync、サーバー上の依存インストール・マイグレーション・キャッシュ再生成を行います。`.env`、`storage`、`vendor` などは rsync 対象外です。

`--skip-build` はフロント変更がない場合、`--allow-dirty` は未コミット内容を送る必要がある場合に限ります。通常はコミット済みの状態で実行します。SSH 接続先や鍵の実値は文書・PR に書きません。

## 定期処理・非同期処理

予約配信・外部同期にはキューワーカー、定期処理には `artisan schedule:run` の毎分実行が必要です。デプロイスクリプトの cron 登録と、利用するキュー方式のワーカーが実際に稼働していることを確認します。Reverb を使う場合は別プロセスと WebSocket プロキシを用意します。

`storage` と `bootstrap/cache` は実行ユーザーが書き込める必要があります。公開アップロード用の `public/storage` リンクと、Web サーバーの公開ルートを確認します。秘密情報を含むアプリディレクトリ全体を公開しません。

## 反映後

- `/health`、管理画面ログイン、LIFF、変更した操作を確認する。
- 外部連携の URL と環境を確認する。Webhook の受信と後処理の完了を区別する。
- ログ・キュー失敗・定期処理を確認する。PHP 8.4 で実行されていることも確認する。
- `.env` を変更した場合は設定キャッシュを再生成する。

デプロイ前の検証は [開発手順](DEVELOPMENT.md)、認証と機密情報の扱いは [セキュリティ](SECURITY.md) に従います。
