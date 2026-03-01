# MobileOrderApp (Skip)

このディレクトリは、`Skip + SwiftUI` で作成した iOS / Android 向け WebView ラッパーです。

## 実装方針

- 画面本体はすべて `WebView`
- ネイティブ UI は最小化し、WebView を全画面表示
- 表示タブは `Skip.env` の `LIFF_FIXED_TAB`（または iOS/Android 個別設定）に固定
- LIFF 側のタブは非表示・遷移ロック（固定タブのみ表示）
- 起動時に `window.__MOBILE_DEVICE_INFO__` へ端末情報を注入し、`/api/devices/register` へ送信
- `window.__nativeSetPushToken(token)` を呼ぶと push token を更新して再登録

## URL設定

LIFF/WebView のベースURLは **プロジェクトルートの `.env` の `APP_URL`** に合わせてください。

- **Laravel（Web）**: `config('app.url')` で `APP_URL` を参照しています。
- **モバイルアプリ**: ビルド時に `Skip.env` の `LIFF_BASE_URL` が使われます。`APP_URL` と揃えてください。

ngrok のURLを変更したときは、次のいずれかを実行してください。

1. **推奨**: プロジェクトルートで同期スクリプトを実行し、その後モバイルをビルドする。
   ```bash
   ./scripts/sync-liff-url-from-env.sh
   ```
2. 手動で `mobile/mobile-order-skip/Skip.env` の `LIFF_BASE_URL` を `.env` の `APP_URL` と同じ値に編集する。

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
cd mobile/mobile-order-skip
swift package resolve
```

### iOS でビルド・起動する

**方法1: Xcode から**

```bash
open Project.xcworkspace
```

Xcode でスキーム「MobileOrderApp App」を選び、シミュレータまたは実機を選択して Run (⌘R) でビルド・起動します。

**方法2: コマンドラインから**

シミュレータ向けにビルドする場合、プラグイン検証をスキップする必要があります。

```bash
# シミュレータ向けビルド（iPhone 16 Pro / iOS 18.5 の例）
xcodebuild -workspace Project.xcworkspace -scheme "MobileOrderApp App" \
  -destination 'platform=iOS Simulator,name=iPhone 16 Pro,OS=18.5' \
  -configuration Debug -skipPackagePluginValidation build
```

ビルド後、シミュレータにインストールして起動します。

```bash
# ビルド成果物のパスは DerivedData により異なります
APP_PATH=$(find ~/Library/Developer/Xcode/DerivedData -name "MobileOrderApp.app" -path "*Debug-iphonesimulator*" -type d | head -1)
xcrun simctl install "iPhone 16 Pro" "$APP_PATH"
xcrun simctl launch "iPhone 16 Pro" jp.gamechanger.mobileorder
```

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

APK はプロジェクトルート（mobile-order-skip）から次の場所に出力されます。

```
.build/Android/app/outputs/apk/debug/app-debug.apk
```

**4. エミュレータ／実機にインストールして起動**

```bash
# プロジェクトルート（mobile-order-skip）で実行。エミュレータまたは実機を 1 台だけ接続した状態で
adb install -r .build/Android/app/outputs/apk/debug/app-debug.apk
adb shell am start -n jp.gamechanger.mobileorder/mobile.order.app.MainActivity
```

`BUILT_PRODUCTS_DIR` の実際のパスは、Xcode でビルドしたあと `~/Library/Developer/Xcode/DerivedData/` 内の `Project-` で始まるフォルダを確認してください。

### Android で画面が真っ白な場合

- **ネットワーク**: エミュレータ／実機から `LIFF_BASE_URL`（例: ngrok の URL）に到達できるか確認してください。エミュレータのブラウザで同じ URL を開いてみます。
- **ログ確認**: `adb logcat` で WebView や SSL 関連のエラーが出ていないか確認します。
- **URL 設定**: `Skip.env` の `LIFF_BASE_URL` がプロジェクトの `.env` の `APP_URL` と一致しているか確認し、必要なら `./scripts/sync-liff-url-from-env.sh` で同期してから再ビルドします。
