# Reggit

LINE ミニアプリと iOS / Android アプリで、会員証・ポイント・スタンプ・クーポン・注文・予約・順番待ちを提供する店舗向けサービスです。スマレジの会員・購買情報と連携し、来店から再来店までを支援します。

PHP 8.4 / Laravel 13、MySQL 8.4、Vite / React / TypeScript を使用します。プロジェクトが契約・ブランド・会員基盤の単位で、その配下に複数の実店舗を持てます。

## 開発を始める

Docker Compose を用意します。PHP は app コンテナ、Node.js 24 は node コンテナで実行します。node_modules は Docker の専用ボリュームに保存します。

```bash
cp .env.example .env
# .env に ADMIN_LOGIN_ID と固有の ADMIN_PASSWORD を設定
# 開発用デモアカウントが必要なら DEMO_SEED=true（本番では禁止）
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan storage:link
docker compose run --rm node npm ci
docker compose run --rm --service-ports node npm run dev -- --host 0.0.0.0
```

初期データが必要な場合は `docker compose exec app php artisan db:seed` を実行します。seed は既存管理者のパスワードやロールを更新しません。

| 入口 | URL |
|---|---|
| アプリ | `http://localhost:8080` |
| 店舗管理 | `/admin/{project_key}/login` |
| システム管理 | `/system-admin/login` |
| LINE ミニアプリ | `/liff/{project_key}` |

DB のホスト公開ポートは `3307`、コンテナ内は `3306` です。ネイティブ PHP / MySQL 環境の起動方法、テスト、DB 接続の切り分けは [開発手順](docs/DEVELOPMENT.md) を参照してください。

## ドキュメント

| 読みたいこと | 文書 |
|---|---|
| 機能の目的・詳細仕様・シーケンス図 | [機能・動作説明書](docs/APP_MANUAL.md) |
| 機能とプロダクトの判断基準 | [プロダクト](docs/PRODUCT.md) |
| 層の責務・テナント分離・設定・イベント | [アーキテクチャ](docs/ARCHITECTURE.md) |
| 実装時の共通ルール | [コーディング規約](CODING_STANDARDS.md) |
| 開発環境・テスト・PR とレビュー | [開発手順](docs/DEVELOPMENT.md) |
| 管理権限・複数店舗・店舗端末・特典取消 | [運用](docs/OPERATIONS.md) |
| LINE・スマレジ・Stripe・Wallet・日本郵便・分析 MCP | [外部連携](docs/INTEGRATIONS.md) |
| 本番設定とデプロイ | [デプロイ](docs/DEPLOY.md) |
| 認証・CSRF・機密情報・確認対象 | [セキュリティ](docs/SECURITY.md) |
| 開発タイムライン（PR・日時） | [開発史](docs/HISTORY.md) |
| 顧客向けネイティブアプリのビルド | [Skip アプリ](mobile/reggit-skip/README.md) |
| 店舗向け iPad アプリのビルド | [Kitchen / Waiting](ios/README.md) |

API の正確なメソッド・パス・ミドルウェアは [routes/api.php](routes/api.php)、画面のルートは [routes/web.php](routes/web.php)、設定値は `.env.example` と `config/` を参照します。文書にルート・カラム・外部 API 仕様の全一覧を複製しません。

ライセンス: MIT。
