# セキュリティ方針と実装

## 1. 認証・認可

### 管理画面（admin guard）
- **セッション認証**: `config/auth.php` の `admin` ガードで `AdminUser` を認証。
- **ロール階層**: システム管理者 > プロジェクト管理者 > 店舗管理者 > スタッフ。上位ロールは下位の操作が可能。
- **プロジェクトスコープ**: 店舗管理は必ず `project_key` 付きURL（`/admin/{project_key}/...`）でアクセス。`BindAdminProject` ミドルウェアでプロジェクトを解決し、他プロジェクトのデータは参照・更新不可。
- **スタッフの権限**: スタッフのみ細かい権限（`admin_user_permissions`）で制御。それ以外のロールは全権限を持つ。

### API
- **Web API（LIFF等）**: セッションまたは `X-Line-User-Id` 等で利用者を識別。`project_key` はクエリで指定。
- **API v1（モバイルアプリ）**: `Authorization: Bearer <token>` または `X-Api-Token`。`AuthenticateApiToken` でトークン検証し、`project_id` でスコープ。トークンは平文を保存せず `token_hash`（SHA-256）のみ保存。

### ログイン試行制限
- 管理画面ログインは **5回/分** まで（`admin-login` レートリミット）。同一 `login_id` + IP でカウント。超過時は HTTP 429。

---

## 2. 入力検証・出力

- **FormRequest**: 全管理画面の登録・更新は FormRequest でバリデーション。`AccountStoreRequest` はパスワードに `Password::defaults()`（8文字以上等）を適用。
- **Blade**: `{{ }}` で自動エスケープ。HTML を出したい場合は `{!! !!}` を避け、必要な場合のみ許可する。
- **監査ログ**: `AdminAuditLogger` で POST/PUT/PATCH/DELETE を記録。`password`, `password_confirmation` は `***` にマスク。

---

## 3. CSRF・ヘッダー

- **CSRF**: `ValidateCsrfToken` を `web` ミドルウェアに含め、管理画面・API（web グループ）のフォームは `@csrf` を付与。
- **API v1**: トークン認証のため CSRF 免除（API ミドルウェアグループ）。トークンは推測困難な乱数で発行。

---

## 4. データベース・機密情報

- **パスワード**: `AdminUser` は `Hash::make()` でハッシュ保存。`casts` で `'password' => 'hashed'` を指定。
- **API トークン**: 平文は発行時のみ表示。DB には `hash('sha256', $token)` のみ保存。
- **環境変数**: `.env` に API キー・DB パスワード等を記載。本番では `.env` をサーバー外に置き、権限を制限。

---

## 5. 権限一覧（スタッフ用）

`App\Models\AdminUserPermission::ALL_PERMISSIONS` で定義。システム管理者は「アカウント管理」でスタッフの権限をチェックボックスで付与・解除可能。  
詳細は `docs/PERMISSIONS.md` を参照。

---

## 6. セキュリティチェック（開発・CI）

- **依存関係の脆弱性**: `make audit`（Docker 内で `composer audit`）を定期的に実行する。問題があれば報告に従いアップデートする。
- **認証・認可のテスト**: `make test-security` で未認証アクセスがログインへリダイレクトされることを検証する（`tests/Feature/SecurityTest.php`）。
- **本番デプロイ前**: 上記に加え、`make test` で全テストが通ることを確認する。

---

## 7. 推奨運用

- 本番では `APP_DEBUG=false`、`APP_ENV=production` にすること。
- 管理画面は HTTPS で提供すること（`AppServiceProvider` で `URL::forceScheme('https')` を本番で有効化済み）。
- API トークンは定期的にローテーションすること。無効化は「APIトークン」画面で revoke/rotate 可能。
- 定期的に `make audit` と `make test-security` を実行し、依存関係と認証まわりを確認すること。
