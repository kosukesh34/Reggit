# Reggit (Skip)

このディレクトリは、`Skip + SwiftUI` で作成した iOS / Android 向け WebView ラッパーです。

## 実装方針

- 画面本体はすべて `WebView`
- ネイティブ UI は最小化し、WebView を全画面表示
- 表示タブは `Skip.env` の `LIFF_FIXED_TAB`（または iOS/Android 個別設定）に固定
- LIFF 側のタブは非表示・遷移ロック（固定タブのみ表示）
- 起動時に `window.__MOBILE_DEVICE_INFO__` へ端末情報を注入し、`/api/devices/register` へ送信
- `window.__nativeSetPushToken(token)` を呼ぶと push token を更新して再登録
- **FCM**: プッシュは Firebase Cloud Messaging。FCM registration token を上記でバックエンドに送り配信する。

## URL設定

LIFF/WebView のベースURLは **プロジェクトルートの `.env` の `APP_URL`** に合わせてください。

- **Laravel（Web）**: `config('app.url')` で `APP_URL` を参照しています。
- **モバイルアプリ**: ビルド時に `Skip.env` の `LIFF_BASE_URL` が使われます。`APP_URL` と揃えてください。

### 開発/本番をコード1箇所で切り替える（推奨）

**Skip.env を触らずに**開発（ngrok）と本番（reggit.jp）を切り替えたい場合は、次の1ファイルだけ編集します。

- **ファイル**: `Sources/Reggit/Data/BundleMobileWebConfigRepository.swift`
- **切り替え方法**:
  - **Debug ビルド** → 自動的に開発用（ngrok）に接続（`#if DEBUG`）
  - **Release ビルド** → Skip.env / Info.plist の URL（本番）に接続
  - ngrok の URL を変えたときは同じファイルの `developmentBaseUrl` を書き換える

環境変数や xcconfig を変えるとビルドや同期に時間がかかるため、開発中はこの切り替えが便利です。

ngrok のURLを変更したときは、次のいずれかを実行してください。

1. **推奨**: プロジェクトルートで同期スクリプトを実行し、その後モバイルをビルドする。
   ```bash
   ./scripts/sync-liff-url-from-env.sh
   ```
2. 手動で `mobile/reggit-skip/Skip.env` の `LIFF_BASE_URL` を `.env` の `APP_URL` と同じ値に編集する。

### 本番（Xserver）向けに接続して起動する

接続先を **本番**（ngrok ではなく）にして iPad や実機で動作確認する手順です。

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
   - 実機は **開発元を信頼**（設定 → 一般 → VPNとデバイス管理）が必要な場合があります。

5. **動作確認**
   - アプリ起動後、WebView が `LIFF_BASE_URL`（`https://reggit.jp`）の LIFF を表示する。
   - 商品一覧・注文・会員などが表示されれば本番接続は成功。

ローカル（ngrok）に戻すときは、`Skip.env` の `LIFF_BASE_URL` を `.env` の `APP_URL`（ngrok の URL）に戻し、再度ビルドする。

## プロジェクト/画面固定の設定

`Skip.env` で、表示するプロジェクトと固定タブを指定できます。

- 共通設定:
  - `LIFF_PROJECT_KEY`（例: `default`）
  - `LIFF_FIXED_TAB`（例: `member`）
- OS別設定（必要な場合のみ）:
  - `LIFF_PROJECT_KEY_IOS` / `LIFF_PROJECT_KEY_ANDROID`
  - `LIFF_FIXED_TAB_IOS` / `LIFF_FIXED_TAB_ANDROID`

優先順は「OS別 > 共通」です。

## 起動手順

### 一括ビルド・インストール（iOS ＋ Android）

プロジェクトルートで以下を実行すると、iOS と Android の両方をビルドし、起動中のシミュレータ／接続中の Android デバイスにインストールします。

```bash
./scripts/build-and-install-mobile.sh
```

- **iOS**: 起動中のシミュレータが 1 台あれば、そこにインストールしてアプリを起動します。
- **Android**: `adb devices` で 1 台だけ接続されている状態で実行すると、そのデバイスに APK をインストールして起動します。
- ビルド先のシミュレータを変えたい場合は `DESTINATION='platform=iOS Simulator,name=iPhone 16 Pro,OS=26.0' ./scripts/build-and-install-mobile.sh` のように指定できます。

### 共通の準備

```bash
cd mobile/reggit-skip
swift package resolve
```

### iOS でビルド・起動する

**方法1: Xcode から**

```bash
open Project.xcworkspace
```

Xcode でスキーム「Reggit App」を選び、シミュレータまたは実機を選択して Run (⌘R) でビルド・起動します。

**方法2: コマンドラインから**

シミュレータ向けにビルドする場合、プラグイン検証をスキップする必要があります。

```bash
# シミュレータ向けビルド（iPhone 16 Pro / iOS 18.5 の例）
xcodebuild -workspace Project.xcworkspace -scheme "Reggit App" \
  -destination 'platform=iOS Simulator,name=iPhone 16 Pro,OS=18.5' \
  -configuration Debug -skipPackagePluginValidation build
```

ビルド後、シミュレータにインストールして起動します。

```bash
# ビルド成果物のパスは DerivedData により異なります
APP_PATH=$(find ~/Library/Developer/Xcode/DerivedData -name "Reggit.app" -path "*Debug-iphonesimulator*" -type d | head -1)
xcrun simctl install "iPhone 16 Pro" "$APP_PATH"
xcrun simctl launch "iPhone 16 Pro" com.reggit.default
```

### Android での表示について

Android の WebView では次の点が iOS と異なります。

- **初回レイアウトで `100vh` が 0 になる**ことがあるため、注入スクリプトで `min-height` に px フォールバック（例: 800px）と `window.innerHeight` の遅延反映を行っています。
- **Skip の Compose が親の制約を正しく受け取らない**場合があるため、`Main.kt` の `PresentationRootView` で `BoxWithConstraints` を使い、コンテンツに明示的なサイズ（`maxWidth` × `maxHeight`）を渡して WebView が画面全体を描画するようにしています。
- タブバーは固定表示され、メインコンテンツ領域は `.main-content` / `.tab-content.active` に `min-height` を指定して潰れないようにしています。

### Android でビルド・起動する

Android の APK をビルドするには、**あらかじめ Xcode で iOS を 1 回ビルドしておく**必要があります。Skip の Gradle ビルドが、その成果物（生成 Kotlin 等）を参照するためです。

**1. 事前に iOS をビルド**

上記「iOS でビルド・起動する」のいずれかで 1 回ビルドしてください。

**2. Android SDK の設定**

`Android/local.properties` に SDK パスを書きます（無い場合のみ）。

```properties
sdk.dir=/Users/<ユーザ名>/Library/Android/sdk
```

**3. APK をビルド**

`BUILT_PRODUCTS_DIR` に、手順 1 の Xcode ビルド成果物のディレクトリを指定して Gradle を実行します。

```bash
# DerivedData のパスは環境により異なります（Xcode で 1 回ビルド後のパスを指定）
export ANDROID_HOME="$HOME/Library/Android/sdk"
export BUILT_PRODUCTS_DIR="$HOME/Library/Developer/Xcode/DerivedData/Project-xxxxx/Build/Products/Debug-iphonesimulator"

cd Android
gradle assembleDebug
```

APK はプロジェクトルート（reggit-skip）から次の場所に出力されます。

```
.build/Android/app/outputs/apk/debug/app-debug.apk
```

**4. エミュレータ／実機にインストールして起動**

```bash
# プロジェクトルート（reggit-skip）で実行。エミュレータまたは実機を 1 台だけ接続した状態で
adb install -r .build/Android/app/outputs/apk/debug/app-debug.apk
adb shell am start -n com.reggit.default/reggit.app.MainActivity
```

`BUILT_PRODUCTS_DIR` の実際のパスは、Xcode でビルドしたあと `~/Library/Developer/Xcode/DerivedData/` 内の `Project-` で始まるフォルダを確認してください。

### プッシュ通知（FCM）

- **バックエンド**: `.env` で `PUSH_FCM_ENABLED=true`、`PUSH_FCM_PROJECT_ID=reggit-dbb5a`、`PUSH_FCM_CREDENTIALS=storage/app/firebase/reggit-dbb5a-adminsdk.json`。
- **iOS**: `GoogleService-Info.plist` + Firebase Messaging。APNs `.p8` は Firebase Console（Cloud Messaging → Reggit Skip）に登録。
- **Android**: `google-services.json` + Firebase Messaging。`push_token` には FCM registration token を保存。
- **テスト**: `docker compose exec app php artisan push:fcm-test --force`

### プッシュ通知のテスト手順

1. アプリを実機でビルド・起動し、通知を許可する（iOS シミュレータでは届かない）。
2. ログインして FCM token が `user_devices` に入るまで待つ。
3. `docker compose exec app php artisan push:fcm-test --force`（本番は `/usr/bin/php8.4 artisan push:fcm-test --force`）。
4. 届かない場合: Firebase Console の APNs キー、Bundle ID、通知許可、DB の token 長さ（旧 OneSignal UUID 36文字は無効）を確認。

### Android で画面が真っ白な場合

- **ネットワーク**: エミュレータ／実機から `LIFF_BASE_URL`（例: ngrok の URL）に到達できるか確認してください。エミュレータのブラウザで同じ URL を開いてみます。
- **ログ確認**: `adb logcat` で WebView や SSL 関連のエラーが出ていないか確認します。
- **URL 設定**: `Skip.env` の `LIFF_BASE_URL` がプロジェクトの `.env` の `APP_URL` と一致しているか確認し、必要なら `./scripts/sync-liff-url-from-env.sh` で同期してから再ビルドします。
