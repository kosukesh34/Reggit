# Reggit

LINE を起点としたモバイルオーダーと会員証（ポイントカード・スタンプ・クーポン）を提供する Web アプリケーションです。LIFF（LINE Front-end Framework）で LINE 内ミニアプリとして動作し、管理画面で店舗ごとの設定・注文・会員・予約・順番待ちなどを一元管理できます。

## 技術スタック

- **バックエンド**: PHP 8.2 / Laravel 10
- **DB**: MySQL 8.0
- **認証**: Laravel Sanctum（API Bearer トークン）、管理画面セッション
- **決済**: Stripe（Payment Intents）
- **連携**: LINE（LIFF・Webhook）、スマレジ（プラットフォーム API）、日本郵便（郵便番号・住所 API）、Apple Wallet / Google Wallet（会員証パス）

## 主な機能

### 顧客向け（LINE / LIFF・Web API）

| 機能 | 説明 |
|------|------|
| 商品・注文 | 商品一覧・注文・注文履歴 |
| 会員 | 会員登録・プロフィール・ポイント・ポイント履歴・会員証（Wallet 連携） |
| 予約 | 予約一覧・空き日取得・予約登録・変更・キャンセル |
| 順番待ち | 順番登録・自分の番号確認・キャンセル・QR ステータス |
| スタンプ・クーポン | スタンプ一覧・マイクーポン |
| お知らせ | お知らせ一覧・既読 |
| ギャラリー | ギャラリー一覧 |
| 決済 | Stripe 決済（create-intent / confirm） |
| 郵便番号検索 | 日本郵便 API による住所自動入力 |

### 管理画面（店舗管理者）

- ダッシュボード、注文・商品・会員・予約・順番待ち・スタンプ・クーポン・お知らせの管理
- 基本設定（営業時間・予約枠・機能フラグ）、詳細設定、メニュー／ホーム／機能／ランク設定
- スマレジ連携設定、LIFF デザイナー（LINE 画面のブロック編集）、ギャラリー、分析、監査ログ

### システム管理（システム管理者）

- プロジェクト（店舗）の作成・編集
- 管理アカウントの CRUD
- API トークン管理（発行・ローテーション・失効）
- 監査ログ

### 外部連携

- **LINE**: Webhook、LIFF（ミニアプリ）
- **スマレジ**: 利用者契約通知（IDP）、Webhook、POS 連携
- **Stripe**: 決済・Webhook
- **日本郵便**: 郵便番号・デジタルアドレス API（住所自動入力）
- **Apple Wallet**: 会員証パス（iOS・オプション）
- **Google Wallet**: 会員証パス（Android 等・オプション）

### iPad アプリ（順番待ち）

店舗での順番待ち表示・呼び出し用の iPad アプリは、別リポジトリで管理しています。本アプリの順番待ち API（`/api/queue/*`）と連携して利用します。

- **リポジトリ**: [kosukesh34/WaitingSystem_iPad](https://github.com/kosukesh34/WaitingSystem_iPad)

## 環境構築

### 必要環境

- Docker / Docker Compose（推奨）
- または PHP 8.2 以上、Composer、MySQL 8.0

### Docker で起動

```bash
# ビルド・起動
docker compose up -d

# 初回のみ: .env を用意し、キー生成・マイグレーション
cp .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

- アプリ: http://localhost:8080  
- DB: ホスト側 `3307` → コンテナ内 `3306`（`.env` の `DB_*` はコンテナ内から見た値を使用）

DB 接続エラー時は `docs/DOCKER_DB_FIX.md` を参照してください。

### .env の主な項目

| 項目 | 説明 |
|------|------|
| `APP_NAME`, `APP_URL` | アプリ名・URL |
| `DB_*` | MySQL 接続情報 |
| `LINE_CHANNEL_ACCESS_TOKEN`, `LINE_CHANNEL_SECRET` | LINE チャネル |
| `LINE_LIFF_ID`, `LINE_LIFF_URL` | LIFF アプリ（管理画面でプロジェクト別に上書き可能） |
| `STRIPE_*` | Stripe キー・Webhook シークレット |
| `SMAREGI_*` | スマレジ クライアント・Webhook（連携時） |
| `JAPANPOST_*` | 日本郵便 API（住所検索、スタブ/本番） |
| `WALLET_PASS_*` | Apple Wallet 会員証（任意） |
| `GOOGLE_WALLET_*` | Google Wallet 会員証（任意。Issuer ID・サービスアカウント JSON・ORIGINS を設定） |

## API

### 公開エンドポイント（認証不要）

- `GET/POST /api/line/webhook` — LINE Webhook
- `POST /api/smaregi/idp` — スマレジ利用者契約通知
- `POST /api/smaregi/webhook` — スマレジ Webhook
- `POST /api/payment/webhook` — Stripe Webhook

### API v1（Bearer トークン・モバイルアプリ用）

プレフィックス: `/api/v1`。`Authorization: Bearer <token>` 必須。

- 商品: `GET /products`, `GET /products/{id}`
- 注文: `GET/POST /orders`, `GET /orders/{id}`
- 会員: `GET /members/me`, `GET /members/points`, `GET /members/points/card`, `GET /members/points/history`
- クーポン: `GET /members/me/coupons`
- スタンプ: `GET /stamps`
- お知らせ: `GET /announcements`, `POST /announcements/{id}/read`
- 予約: `GET /reservations`
- 決済: `POST /payment/create-intent`, `POST /payment/confirm`

### Web API（LIFF・セッション / プロジェクト紐付け）

`/api` 以下。ミドルウェア `web` + `bind.project` でプロジェクト紐付け。

- 商品・注文・会員・郵便番号・Wallet パス・会員登録・ポイント・クーポン
- 決済・予約（一覧・空き日・登録・更新・キャンセル）
- お知らせ・お問い合わせ・ギャラリー・スタンプ
- 順番待ち（登録・自分の番・ステータス・キャンセル・次番呼び出し・QR）

### Wallet（Apple / Google）

- **iOS**: Apple Wallet に会員証を追加可能（`WALLET_PASS_*` 設定時）。会員証タブで「Walletに追加」をタップすると .pkpass を取得して追加できる。
- **Android**: Google Wallet に会員証を追加可能（`GOOGLE_WALLET_*` 設定時）。会員証タブに「Google Walletに追加」が表示され、タップで API から URL を取得し Google Wallet の保存画面へ遷移する。端末種別に応じて Apple / Google のいずれかが表示される。

Google Wallet を有効にするには、[Google Pay & Wallet Console](https://pay.google.com/business/console) で Issuer を登録し、Issuer ID とサービスアカウントの秘密鍵 JSON を取得して `.env` の `GOOGLE_WALLET_*` を設定する。`GOOGLE_WALLET_ORIGINS` には会員証を表示するドメイン（LIFF の URL など）をカンマ区切りで指定する。

## 管理画面 URL

- ログイン: `/admin/login` または `/admin/{project_key}/login`（例: `/admin/default/login`）
- 店舗管理: `/admin/{project_key}/dashboard` など
- システム管理: `/system-admin/login` → `/system-admin/dashboard`
- LIFF エントリ: `/liff` または `/liff/{project_key}`

**ログインできない場合**  
- パスワードに `#` を含む場合、.env で値全体を `"` で囲んでください（例: `DEMO_SYSTEM_PASSWORD="DemoSys#2026"`）。`#` はコメント扱いされ、それ以降が無視されます。  
- .env を直したら、DB に反映するため次を実行してください。

```bash
docker compose exec app php artisan admin:sync-password
```

（省略時は `DEMO_SYSTEM_LOGIN_ID` のユーザーを `DEMO_SYSTEM_PASSWORD` で更新します。別の login_id を指定する場合は `admin:sync-password <login_id>`）

## ドキュメント

- [本番デプロイ（`reggit deploy`）](docs/DEPLOY.md)
- [機能一覧・仕様](docs/FEATURES_SPEC.md)
- [権限一覧](docs/PERMISSIONS.md)
- [URL 一覧](docs/URL_LIST.md)
- [E2E テスト（Playwright / Maestro）](docs/E2E.md)
- [スマレジ連携](docs/SMAREGI_SETUP.md) / [Webhook テスト](docs/SMAREGI_WEBHOOK_TEST.md) / [POS API リファレンス](docs/SMAREGI_POS_API_REFERENCE.md)
- [日本郵便 API](docs/JAPANPOST_API.md)
- [Google Wallet 会員証セットアップ](docs/GOOGLE_WALLET_SETUP.md) / [**やることチェックリスト**](docs/GOOGLE_WALLET_CHECKLIST.md) / [**Wallet 追加のアクセス用URL一覧**](docs/WALLET_ADD_URLS.md)
- [Docker DB 接続トラブル](docs/DOCKER_DB_FIX.md)
- [セキュリティ](docs/SECURITY.md)
- [クリーンアーキテクチャ](docs/CLEAN_ARCHITECTURE.md)

## ライセンス

MIT
