# E2E テスト（Playwright / Maestro）

Web は Playwright、ネイティブ（Skip）は Maestro でスモークを回します。CI は GitHub Actions（[`.github/workflows/ci.yml`](../.github/workflows/ci.yml)）です。

## Web（Playwright）

前提: Docker でアプリが起動済み（`make setup-dev` 推奨）。URL は `http://localhost:8080`。

```bash
npm ci
npx playwright install chromium
E2E_BASE_URL=http://localhost:8080 npm run test:e2e
# または
make e2e-web
```

初回シナリオ: 店舗管理者（`demo_store` / `DemoStore#2026`）で `/admin/login` → ダッシュボード表示。

設定ファイル: [`e2e/web/playwright.config.ts`](../e2e/web/playwright.config.ts)

## Native（Maestro / Android）

前提:

1. Maestro CLI が入っていること（[公式](https://maestro.mobile.dev)）
2. Android エミュレータまたは実機が `adb` で見えること
3. APK がインストール済み（Skip は **macOS + Xcode で先に iOS ビルドが必要**）

```bash
./scripts/build-and-install-mobile.sh
./scripts/e2e-mobile-android.sh
# または
make e2e-mobile
```

`Unable to launch app com.reggit.default` のときは、エミュレータに APK が入っていません。`e2e-mobile-android.sh` は未インストールなら既存 APK を `adb install` します。それでも `INSTALL_FAILED_INSUFFICIENT_STORAGE` なら AVD の **Wipe Data** か空き確保が必要です（`adb shell df -h /data`）。

### カバー範囲

| フロー | 内容 |
|--------|------|
| [`launch.yaml`](../e2e/mobile/flows/launch.yaml) | 起動 →（任意）通知許可 → 「Reggit」表示 |
| [`wallet-google.yaml`](../e2e/mobile/flows/wallet-google.yaml) | 会員タブ → 会員証 →「Google Walletに追加」→ Google の保存 UI が開く |

**Wallet フローの前提**

- 接続先で `GOOGLE_WALLET_ENABLED=true`（かつ Google Wallet の資格情報が有効）
- **会員登録済み**であること（サンプル会員証ではボタン自体が出ない）
- ネイティブ下部タブに「会員」があること（初期タブが `products` でも、タブをタップして遷移）

**意図的に見ていないこと**

- Google アカウントログイン後の「Wallet への保存完了」そのもの（外部アカウント操作が必要で CI 向きでない）
- Apple Wallet（Maestro CI は Android 優先。iOS は PassKit シートで別途）

### CI 上の注意（GitHub Actions）

- `e2e_web` は Ubuntu で MySQL + `artisan serve` のうえ Playwright を実行します（必須チェック）。`storage/framework` は gitignore のため CI でディレクトリ作成が必要です。
- `e2e_mobile` は `macos-latest` で Skip ビルド → Android エミュレータ → Maestro。Skip / AVD / Wallet 資格情報の都合で失敗しうるため当面 **`continue-on-error: true`** です。
- GitLab CI（`.gitlab-ci.yml`）は使いません。
