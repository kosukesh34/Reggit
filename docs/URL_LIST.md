# ログイン画面・LINE画面 URL 一覧

## アプリの起動方法（Docker）

```bash
# コンテナ起動（DB・アプリ・Nginx）
docker compose up -d

# 初回またはマイグレーションが必要な場合
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

**アクセス先**: ブラウザで **http://localhost:8080** を開く（`.env` の `APP_URL` が `http://localhost:8080` の場合）。

### ログイン用アカウント（シード済み）

| 種別 | ログインID | パスワード | ログインURL |
|------|------------|------------|-------------|
| システム管理者 | `demo_sys` | `DemoSys#2026` | http://localhost:8080/system-admin/login |
| プロジェクト管理者 | `demo_proj` | `DemoProj#2026` | http://localhost:8080/admin/{project_key}/login |
| 店舗管理者 | `demo_store` | `DemoStore#2026` | http://localhost:8080/admin/{project_key}/login |
| 初回システム管理者（.env） | `ADMIN_LOGIN_ID` または `ADMIN_EMAIL` に設定した値 | `ADMIN_PASSWORD` に設定した固有の値 | http://localhost:8080/system-admin/login |

※ **システム管理者**は `/system-admin/login` からのみログインでき、**全プロジェクト**にアクセス可能です。
※ **店舗・プロジェクト管理者**は `/admin/login`（ID・パスワードのみ）または `/admin/{project_key}/login` でログインします。プロジェクト一覧は公開しません。

---

## ログイン画面

| 画面 | URL | ルート名 | 説明 |
|------|-----|----------|------|
| 店舗管理ログイン | `/admin/login` | `admin.login` | ID・パスワードでログイン（プロジェクト一覧は非表示） |
| プロジェクト別ログイン | `/admin/{project_key}/login` | `admin.project.login` | 指定プロジェクトのログイン画面（URLを知っている場合） |
| システム管理者ログイン | `/system-admin/login` | `system-admin.login` | **システム管理者専用**（GET: 表示 / POST: 認証）。システム管理者のみログイン可。 |

### 補足

- **店舗・プロジェクト管理者**: `/admin/login` または `/admin/{project_key}/login` でログイン。ログイン後は許可されたプロジェクトのダッシュボードへ遷移します。
- **システム管理者**: `/system-admin/login` からログイン。**システム管理者ロールのアカウントのみ**ログイン可能。ログイン後は `/system-admin/dashboard` でプロジェクト・アカウント・API・監査ログを管理し、**全プロジェクトの店舗管理画面にもアクセス**できます。システム管理画面（`/system-admin/*`）はシステム管理者以外は 403 です。
- ベースURLは `APP_URL`（`.env` の `APP_URL`、未設定時は `http://localhost`）です。

**例（ローカル）**
- 店舗管理ログイン: `http://localhost:8080/admin/login`
- プロジェクト別ログイン: `http://localhost:8080/admin/sb_skp835t6/login`（`project_key` はプロジェクトごと）
- システム管理ログイン: `http://localhost:8080/system-admin/login`

---

## LINE画面（LIFF）

| 画面 | URL | ルート名 | 説明 |
|------|-----|----------|------|
| LIFF メイン画面（プロジェクト別） | `/liff/{project_key}` | `liff.index` | LINE内/ブラウザで開くお客様向けメイン画面（プロジェクトごと） |
| LIFF エントリ（リダイレクト） | `/liff` | （リダイレクト） | プロジェクト解決後で `/liff/{project_key}` へ 302 リダイレクト |

### プロジェクトの指定方法

LIFF は **URL パスでプロジェクトを分けます**。

1. **パス**（推奨・プロジェクト別URL）
   - `/liff/{project_key}`
   - 例: `http://localhost:8080/liff/default` / `http://localhost:8080/liff/shop1`

2. **`/liff` のみアクセスした場合**
   - ミドルウェアでプロジェクトを解決（クエリ `project_key`、`liffRedirectUri`、`state`、ヘッダー `X-Project-Key`、未指定時は default）し、`/liff/{解決したproject_key}` へリダイレクト

3. **ヘッダー**（API等）
   - `X-Project-Key: {project_key}`

### 補足

- LINE の LIFF アプリとして登録する URL は、**プロジェクトごとに** `APP_URL` + `/liff/{project_key}` を LINE 開発者コンソールの LIFF Endpoint URL に設定します（例: `https://example.com/liff/shop1`）。
- 認証後のリダイレクトで `liffRedirectUri` や `state` に URL が含まれる場合、そのクエリから `project_key` が取り出され、`/liff/{project_key}` へリダイレクトされます。

**例（ローカル）**
- デフォルト: `http://localhost:8080/liff` → `http://localhost:8080/liff/default` へリダイレクト
- プロジェクト指定: `http://localhost:8080/liff/default` / `http://localhost:8080/liff/shop1`

---

## 参考

- ルート定義: `routes/web.php`
- LIFF 用プロジェクト解決: `app/Http/Middleware/BindProjectFromRequest.php`
- LINE 設定: `config/line.php`（`LINE_LIFF_ID`, `LINE_LIFF_URL`）
