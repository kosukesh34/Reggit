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
`admin-home-images.spec.ts` は管理画面でホーム画像を2枚追加し、APIとプレビューへの反映を確認して追加分を削除する。
`admin-main-screens.spec.ts` は店舗管理のメニューと設定配下の画面を巡回する。`customer-screens.spec.ts` は顧客向けの主要25画面を巡回する。

設定ファイル: [`e2e/web/playwright.config.ts`](../e2e/web/playwright.config.ts)

## Native（Maestro / Android）

前提:

1. Maestro CLI が入っていること（[公式](https://maestro.mobile.dev)）
2. Android エミュレータまたは実機が `adb` で見えること
3. APK がインストール済み（Skip は **macOS + Xcode で先に iOS ビルドが必要**）

```bash
./scripts/build-and-install-mobile.sh
./scripts/e2e-mobile-android.sh
# 複数エミュレータがある場合
ANDROID_SERIAL=emulator-5556 ./scripts/e2e-mobile-android.sh
# アプリ情報のみ
./scripts/e2e-mobile-android.sh info
# ホーム画像のみ
./scripts/e2e-mobile-android.sh home
# Google Wallet 連携の資格情報がある環境のみ
./scripts/e2e-mobile-android.sh wallet
# または
make e2e-mobile
```

`Unable to launch app com.reggit.default` のときは、エミュレータに APK が入っていません。`e2e-mobile-android.sh` は未インストールなら既存 APK を `adb install` します。それでも `INSTALL_FAILED_INSUFFICIENT_STORAGE` なら AVD の **Wipe Data** か空き確保が必要です（`adb shell df -h /data`）。

### カバー範囲

| フロー | 内容 |
|--------|------|
| [`launch.yaml`](../e2e/mobile/flows/launch.yaml) | 起動 →（任意）通知許可 → メニューボタン表示 |
| [`home-image.yaml`](../e2e/mobile/flows/home-image.yaml) | ホーム画像をタップ → 全画面表示 → 閉じる |
| [`app-info.yaml`](../e2e/mobile/flows/app-info.yaml) | メニュー → アプリ情報 → 端末・バージョン・ユーザーID・時刻を確認 |
| [`main-screens.yaml`](../e2e/mobile/flows/main-screens.yaml) | ホーム・モバイル注文・あそびの主要画面と3種類の抽選画面を巡回 |
| [`mobile-order.yaml`](../e2e/mobile/flows/mobile-order.yaml) | 商品を追加 → 数量を減らす → 追加表示に戻る |
| [`wallet-google.yaml`](../e2e/mobile/flows/wallet-google.yaml) | 会員タブ → 会員証 →「Google Walletに追加」→ Google の保存 UI が開く |

引数なしでは `launch`・`home-image`・`app-info`・`main-screens`・`mobile-order` を実行する。Google Wallet は外部連携の前提があるため `wallet` を指定して実行する。

**Wallet フローの前提**

- 接続先で `GOOGLE_WALLET_ENABLED=true`（かつ Google Wallet の資格情報が有効）
- **会員登録済み**であること（サンプル会員証ではボタン自体が出ない）
- ネイティブ下部タブに「会員」があること（初期タブが `products` でも、タブをタップして遷移）

**意図的に見ていないこと**

- Google アカウントログイン後の「Wallet への保存完了」そのもの（外部アカウント操作が必要で CI 向きでない）
- Apple Wallet（Maestro CI は Android 優先。iOS は PassKit シートで別途）

### CI 上の注意（GitHub Actions）

- **日常のマージ先は `main`**（feature / chore PR の base）。
- **PR / feature など**: `test` + `lint` のみ。
- **`e2e_web`（Playwright）は一時停止中**（`ci.yml` で `if: false`）。ローカルでは `make e2e-web` / `npm run test:e2e` を任意実行可。再開時は `push` かつ `main` の条件に戻す。
- `e2e_mobile` も当面 **`if: false` でスキップ**（再開時は条件を外す）。
- GitLab CI（`.gitlab-ci.yml`）は使いません。
