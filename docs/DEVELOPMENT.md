# 開発・テスト・レビュー

## 環境

PHP 8.4、Composer、MySQL 8、Node.js `^20.19.0` または `>=22.12.0` を使います。Docker の初回セットアップは [README](../README.md)、実行コマンドの定義は [Makefile](../Makefile)、`composer.json`、`package.json` を参照してください。

Laravel 13 / React 19 / Vite 8 を使用します。Docker の Node サービスは Node.js 24 です。TypeScript は typescript-eslint が対応する 6.0 系、Guzzle は Reverb が対応する 7 系を使用します。Stripe SDK は 22 系ですが、決済 API のバージョンは従来の `2026-02-25.clover` を維持しています。

Docker の `.env` は `DB_HOST=db`、`DB_PORT=3306`。ホストから接続する場合は `127.0.0.1:3307` です。コンテナの起動前に `.env` を用意します。

Docker を使わない環境は `DB_HOST=127.0.0.1` と実際の MySQL ポートを設定し、Composer / npm のコマンドを直接実行します。Linux VM の MySQL は必要に応じて `sudo service mysql start` で起動します。アプリと Vite は別プロセスで起動します。

```bash
php artisan serve --host=0.0.0.0 --port=8000
npm run dev
```

ネイティブ環境では Docker を包む Makefile ターゲットを使いません。Vite を起動しない場合は `npm run build` でアセットを生成します。

## 管理者とサンプルデータ

初回 seed には `ADMIN_LOGIN_ID`（または `ADMIN_EMAIL`）と固有の `ADMIN_PASSWORD` が必要です。空値と旧既定値 `reserve` は拒否します。seed を再実行しても既存アカウントのパスワード・ロールは変わりません。

開発用デモは `DEMO_SEED=true` で作成できます。本番では使用できません。既定のデモログインは次のとおりです。

| 用途 | ログインID | パスワード |
|---|---|---|
| 店舗管理 | `demo_store` | `DemoStore#2026` |
| システム管理 | `demo_sys` | `DemoSys#2026` |

`.env` の `#` を含む値はダブルクォートで囲みます。既存のデモパスワードを明示的に反映する場合は `php artisan admin:sync-password <login_id>` を使い、環境変数と対象を確認してから実行します。

`db:seed` は最後にスマレジ同期を試みます。資格情報がない場合の同期失敗は、サンプル投入済みなら開発開始を妨げません。

## テストと静的検査

`.env.testing` は `env.testing.example` から作成し、接続先をテスト用 `reggit_test` にします。開発用 `reggit` を指定しません。`phpunit.xml` と `InteractsWithTestDatabase` に DB のガードがあります。

```bash
cp env.testing.example .env.testing
# ネイティブ実行なら .env.testing の DB_HOST / DB_PORT も合わせる
php artisan test --exclude-group=smaregi-integration
./vendor/bin/pint --test
composer analyse
npm run typecheck
npm run typecheck:strict
npm run lint
npm run test:unit:frontend
```

Docker では PHP / Composer のコマンドに `docker compose exec app`、npm のコマンドに `docker compose run --rm -T node` を付けます。整形の自動修正は `./vendor/bin/pint`、フロントの配布物は `npm run build` です。Node コンテナの依存は専用ボリュームに保存されるため、初回は `docker compose run --rm -T node npm ci` を実行します。

スマレジ実接続テストは外部資格情報とテスト契約が必要です。ダミー値では自動スキップされず失敗する場合があるため、通常のコア検証では上記グループを除外します。実接続手順は [外部連携](INTEGRATIONS.md#スマレジ) を参照してください。

`Tests\TestCase` は通常 CSRF を無効化します。CSRF の回帰試験は `ApiCsrfSecurityTest` のようにミドルウェアを有効化し、Laravel のテスト時スキップも無効化して検証します。

## E2E

Web は `make e2e-web`（`scripts/e2e-web.sh`）で実行します。開発用の `.env`・DB `reggit`・`storage/logs` は使わず、E2E 専用コンテナ（`http://localhost:8090`）と使い捨て DB `reggit_e2e` を作り、終了時に両方を削除します。ログはコンテナの標準エラーに出し、アップロード画像などの storage もコンテナ内の一時領域に置くため、テストの痕跡は残りません。設定は `env.e2e.example` です。管理画面のパスキー確認に `localhost` が必要なため、Playwright は Node コンテナではなくホストで動かします。初回だけホストで `npm ci && npx playwright install chromium` を実行します。

```bash
make e2e-web
./scripts/e2e-web.sh customer-shell   # spec を絞る
E2E_CONFIG=e2e/web/security-review.config.ts E2E_BROWSER_CHANNEL=chrome ./scripts/e2e-web.sh
```

接続先は `E2E_BASE_URL`、ログインは `E2E_ADMIN_LOGIN_ID` / `E2E_ADMIN_PASSWORD` で指定できます。管理画面、顧客画面、画像設定、ダイアログなどの具体的な対象は `e2e/web/` の spec を参照します。

ネイティブは APK と Android 実機 / エミュレータ、ADB、Maestro を準備します。

```bash
./scripts/build-and-install-mobile.sh
./scripts/e2e-mobile-android.sh
# 複数端末なら ANDROID_SERIAL=emulator-5556 を指定
./scripts/e2e-mobile-android.sh wallet
```

通常実行は起動・ホーム画像・アプリ情報・主要画面・注文を検証します。`wallet` は有効な Google Wallet 設定と登録済み会員が必要です。Skip のビルドは [ネイティブ README](../mobile/reggit-skip/README.md) を参照してください。CI で実際に有効なジョブは `.github/workflows/ci.yml` が正です。

## DB 接続の切り分け

1. `docker compose ps` / `docker compose logs db` で DB の起動を確認する。
2. `.env` のホスト・ポート・DB 名・ユーザーと、実行場所の対応を確認する。
3. `.env` を変更したら `php artisan config:clear` を実行する。
4. 既存 DB ボリュームのユーザー情報は `.env` の変更だけでは更新されない。DB 管理者でログインし、必要なユーザーと対象 DB の権限を確認する。

接続エラーの解消のために DB ボリュームを削除しません。再作成が必要な場合は、データの退避と復元方法を先に決めます。

## PR とコードレビュー

機能・修正ごとにブランチを作り、1 PR を1目的にします。コミットは論理単位にまとめ、秘密情報・生成キャッシュ・無関係な整形差分を含めません。

PR 本文には「問題と変更後の挙動」「影響範囲」「検証結果」を書きます。テンプレートは [共通テンプレート](../.github/PULL_REQUEST_TEMPLATE.md) だけを使います。

```bash
git push -u origin HEAD
gh pr create --base main --title "fix: 変更内容" --body-file /path/to/pr-body.md
```

レビューは変更箇所と影響範囲を対象に、次の順に確認します。

1. 層と依存の向き、UseCase の責務、既存実装との整合。
2. ソート・分岐・レスポンス項目・状態遷移の退行。
3. テナント・店舗・所有者の認可、入力検証、機密情報、二重処理。
4. 必要な正常系・異常系のテストと CI の結果。
5. [コーディング規約](../CODING_STANDARDS.md) への準拠。

レビュー指摘を解消し、CI と承認を確認してからマージします。実行できなかった検証は、成功扱いにせず理由を記載します。
