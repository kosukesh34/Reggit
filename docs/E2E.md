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

## Native（Maestro）

### CI（必須）: iOS Simulator 起動スモーク

GitHub Actions `macos-latest` では **iOS Simulator + Maestro の起動スモーク**を必須チェックにしています。

```bash
./scripts/e2e-mobile-ios-ci.sh
```

フロー: [`e2e/mobile/flows/launch.yaml`](../e2e/mobile/flows/launch.yaml)（起動 → 通知許可任意 → 「Reggit」表示）

### ローカル（任意）: Android + Google Wallet

前提:

1. Maestro CLI（[公式](https://maestro.mobile.dev)）
2. Android エミュレータ / 実機が `adb` で見えること
3. APK インストール済み（Skip は **macOS + Xcode で先に iOS ビルドが必要**）

```bash
./scripts/build-and-install-mobile.sh
./scripts/e2e-mobile-android.sh
# または
make e2e-mobile
```

`Unable to launch app com.reggit.default` のときは、エミュレータに APK が入っていません。`e2e-mobile-android.sh` は未インストールなら既存 APK を `adb install` します。それでも `INSTALL_FAILED_INSUFFICIENT_STORAGE` なら AVD の **Wipe Data** か空き確保が必要です（`adb shell df -h /data`）。

| フロー | 内容 |
|--------|------|
| [`launch.yaml`](../e2e/mobile/flows/launch.yaml) | 起動 →（任意）通知許可 → 「Reggit」表示（**CI 必須**） |
| [`wallet-google.yaml`](../e2e/mobile/flows/wallet-google.yaml) | 会員タブ →「Google Walletに追加」→ Google 保存 UI（**ローカル Android**） |

**Wallet フローの前提**

- 接続先で `GOOGLE_WALLET_ENABLED=true`
- 会員登録済み（サンプル会員証ではボタン非表示）

**意図的に見ていないこと**

- Google アカウントでの最終「Wallet への保存完了」
- CI 上の Android / Google Wallet（環境差が大きく必須化しない）

### CI 上の注意（GitHub Actions）

- `e2e_web` / `e2e_mobile` は必須。`storage/framework` は gitignore のため Web CI でディレクトリ作成が必要。
- `e2e_mobile` は利用可能な iPhone Simulator を自動選択する（固定の `iPhone 16` 依存をやめた）。
- GitLab CI（`.gitlab-ci.yml`）は使いません。
