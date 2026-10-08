# AGENTS.md

## マージ・E2E の必須ルール

- E2E・ブラウザーテストはローカルで実行する。GitHub Actions への追加・実行は禁止。必要なアプリ・DB は既存の実行に干渉しないローカル環境を使用し、実行結果を PR に記載する。
- マージ前に、PR の最新 HEAD に対する CI の `test`・`lint`・`main` がすべて成功したことを確認する。他のチェックも失敗・保留・キャンセル・不明ならマージしない。
- push 後は以前のコミットの成功結果を流用せず、最新 HEAD のチェック完了を待つ。CI の無効化、失敗を無視したマージ、`--admin` による迂回は禁止。
- マージには `scripts/merge-pr.sh <PR番号>` を使用する。ユーザーのマージ承認は別途必要（会話内の明示的な承認を引き継ぐ）。
- GitHub のプランが対応する場合は main の branch protection/ruleset でも `main` を required status check に設定する。設定できない場合も上記ルールを必ず守る。


## Cursor Cloud specific instructions

Reggit は PHP 8.4 / Laravel 12 + Vite/React（管理画面・LIFF）+ MySQL のマルチテナント Web アプリです。リポジトリ本来のワークフローは Docker Compose（`setup.sh` / `Makefile` / `README.md` はすべて `docker-compose exec app ...` 前提）ですが、**Cloud Agent の VM では Docker を使わずネイティブに PHP・MySQL・Node を動かしています**。以下はネイティブ環境特有の非自明な注意点のみを記載します。標準的なコマンドは `README.md` / `Makefile` / `composer.json` / `package.json` を参照してください。

### 重要な差分（Docker → ネイティブ）
- `Makefile` の各ターゲットは `docker-compose exec app` を包むため **この VM では動きません**。中身の `php artisan ...` / `npm ...` を直接実行してください（例: `make test` の代わりに `php artisan test`）。
- `.env` / `.env.testing` の `DB_HOST` は Docker のサービス名 `db` ではなく **`127.0.0.1`** に設定済みです（MySQL は VM 上でネイティブ起動）。`.env` は gitignore 対象で、更新スクリプトでは再生成しません。

### サービスの起動（毎セッション必要）
- MySQL は自動起動しないため、作業開始時に起動する: `sudo service mysql start`
- DB: 開発用 `reggit` / テスト用 `reggit_test`、ユーザー `reggit_user` / パスワード `password`。
- アプリ起動（dev）: `php artisan serve --host=0.0.0.0 --port=8000` と `npm run dev`（Vite HMR）を別々に起動。管理画面 Blade は `@vite` で Vite dev サーバー（:5173）からアセットを読むため、UI を触る場合は `npm run dev` も必要。
- アクセス先: アプリ `http://localhost:8000`
  - 店舗管理ログイン: `/admin/default/login`（`demo_store` / `DemoStore#2026`）
  - システム管理ログイン: `/system-admin/login`（`demo_sys` / `DemoSys#2026`）
  - LIFF: `/liff/default`

### Lint / テスト
- Lint（整形チェック）: `./vendor/bin/pint --test`（自動修正は `./vendor/bin/pint`）。
- テスト: `php artisan test`。`phpunit.xml` が `APP_ENV=testing` と `DB_DATABASE=reggit_test` を強制するため `.env.testing` を読み、開発 DB `reggit` は破壊されません。

### 既知のハマりどころ
- `tests/Feature/SmaregiApiIntegrationTest.php`（`@group smaregi-integration`、3 テスト）は**スマレジ外部 API への実接続**テストです。`env.testing.example` がダミー資格情報を含むため空判定でのスキップが効かず、有効な `SMAREGI_CLIENT_ID` / `SMAREGI_CLIENT_SECRET` が無いと **失敗**します（コア 670 テストは成功）。実接続を検証しない限りこの 3 件の失敗は想定内です。
- `php artisan db:seed` は最後にスマレジ同期を試み、資格情報が無いと「スマレジ同期に失敗しました」と出ますが **DB のサンプルデータ投入は完了しており非ブロッキング**です。
- LINE / Stripe / スマレジ / 日本郵便 / Apple・Google Wallet / Push はすべて任意の外部連携で、デフォルト無効またはスタブです。コア開発・テストには不要です。
