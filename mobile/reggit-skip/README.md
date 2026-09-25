# Reggit (Skip)

このディレクトリは、`Skip + SwiftUI` で作成した iOS / Android 向け **ネイティブ顧客アプリ**です。

## 対象プラットフォームと実装基準

- Apple側のアプリターゲットは **iOS 17以降（iPhone / iPad）** です。macOS / Mac Catalyst は対象外です。
- UI実装はiOSのSwiftUIを基準にし、Skip Fuseが同じコードをAndroidのネイティブUIへ橋渡しします。
- 通常の共通UIで `canImport` は使いません。プラットフォーム差が必要な場合は、Skip公式の推奨に従って `#if os(Android)` / `#if !os(Android)` を使います。
- `#if SKIP` は、Kotlin / Java APIを直接呼ぶ必要がある箇所だけに限定します。
- iOS専用フレームワークを使う場合は `#if !os(Android)` で囲み、Android側には代替実装または明示的なフォールバックを用意します。

## 実装方針

- 画面は SwiftUI（主要タブ: 会員・注文・クーポン・スタンプ・待ち順）
- データは `/api/*` を直接呼び出し（`X-Device-Identifier` + `X-Project-Key`）
- 起動時に `POST /api/devices/register` で端末登録（push token 含む）
- タブ構成は `GET /api/v1/app-config`（失敗時はデフォルト5タブ）
- **FCM**: プッシュは Firebase Cloud Messaging。registration token をデバイス登録で送る

## URL設定

API のベースURLは **プロジェクトルートの `.env` の `APP_URL`** に合わせてください。

- **Laravel（Web）**: `config('app.url')` で `APP_URL` を参照しています。
- **モバイルアプリ**: ビルド時に `Skip.env` の `LIFF_BASE_URL` が使われます（API base として利用）。`APP_URL` と揃えてください。

### 開発/本番をコード1箇所で切り替える（推奨）

**Skip.env を触らずに**開発（ngrok）と本番（reggit.jp）を切り替えたい場合は、次の1ファイルだけ編集します。

- **ファイル**: `Sources/Reggit/Data/BundleMobileWebConfigRepository.swift`
- **切り替え方法**:
  - **Debug ビルド** → 自動的に開発用（ngrok）に接続（`#if DEBUG` と `useDevelopmentServer`）
  - **Release ビルド** → Skip.env / Info.plist の URL（本番）に接続
  - ngrok の URL を変えたときは同じファイルの `developmentBaseUrl` を書き換える

ngrok のURLを変更したときは、次のいずれかを実行してください。

1. **推奨**: プロジェクトルートで同期スクリプトを実行し、その後モバイルをビルドする。
   ```bash
   ./scripts/sync-liff-url-from-env.sh
   ```
2. 手動で `mobile/reggit-skip/Skip.env` の `LIFF_BASE_URL` を `.env` の `APP_URL` と同じ値に編集する。

### 本番（Xserver）向けに接続して起動する

1. **接続先を本番にしておく**
   `Skip.env` の `LIFF_BASE_URL` / `LIFF_BASE_URL_IOS` を **`https://reggit.jp`** に設定する。

2. **パッケージ解決**
   ```bash
   cd mobile/reggit-skip
   swift package resolve
   ```

3. **Xcode で開く**
   ```bash
   open Project.xcworkspace
   ```

4. **iPad / 実機を選んでビルド・起動**
   - メニュー **Product** → **Destination** で接続した iPad または実機を選択。
   - **Run (⌘R)** でビルドし、実機にインストールして起動する。

5. **動作確認**
   - アプリ起動後、ネイティブタブ（会員・注文など）が表示される。
   - 会員証・商品一覧などが API 経由で取れていれば本番接続は成功。

ローカル（ngrok）に戻すときは、`Skip.env` の `LIFF_BASE_URL` を `.env` の `APP_URL`（ngrok の URL）に戻し、再度ビルドする。

## プロジェクト/画面固定の設定

`Skip.env` で、表示するプロジェクトと初期タブを指定できます。

- 共通設定:
  - `LIFF_PROJECT_KEY`（例: `default`）
  - `LIFF_FIXED_TAB`（例: `home`）— 起動時の初期タブ
- OS別設定（必要な場合のみ）:
  - `LIFF_PROJECT_KEY_IOS` / `LIFF_PROJECT_KEY_ANDROID`
  - `LIFF_FIXED_TAB_IOS` / `LIFF_FIXED_TAB_ANDROID`

許可タブキー: `home`, `products`, `orders`, `stamps`, `coupons`, `queue`, `member`, `reservations`, `gallery`
（ネイティブ UI で実装済み: `member`, `products`, `coupons`, `stamps`, `queue`）

## 認証

顧客本人特定は **`X-Device-Identifier`** です（管理用 Bearer `ApiToken` は使わない）。

1. 端末 UUID を UserDefaults に永続化
2. `POST /api/devices/register`
3. 以降の `/api/members/*` 等が device→user で解決される

## Android APK

Android の APK をビルドするには、**あらかじめ Xcode で iOS を 1 回ビルドしておく**必要があります。Skip の Gradle ビルドが、その成果物（生成 Kotlin 等）を参照するためです。

```bash
cd mobile/reggit-skip/Android
./gradlew :app:assembleDebug
```

## プッシュ（FCM）

- **iOS**: `GoogleService-Info.plist` + Firebase Messaging。APNs `.p8` は Firebase Console に登録。
- **Android**: `google-services.json` + FCM。トークンは SharedPreferences（Skip UserDefaults）へ保存。

## トラブルシュート

- **ログ確認**: iOS は Xcode コンソール、Android は `adb logcat`。
- **URL 設定**: `Skip.env` の `LIFF_BASE_URL` がプロジェクトの `.env` の `APP_URL` と一致しているか確認。
