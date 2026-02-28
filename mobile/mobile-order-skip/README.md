# MobileOrderApp (Skip)

このディレクトリは、`Skip + SwiftUI` で作成した iOS / Android 向け WebView ラッパーです。

## 実装方針

- 画面本体はすべて `WebView`
- 下部のみネイティブタブボタン
- タブ押下時に Web 側 (`/liff`) のタブを JavaScript で切り替え
- 上部ヘッダーにネイティブの戻るボタンを配置
- 起動時に `window.__MOBILE_DEVICE_INFO__` へ端末情報を注入し、`/api/devices/register` へ送信
- `window.__nativeSetPushToken(token)` を呼ぶと push token を更新して再登録

## URL設定

`Sources/MobileOrderApp/ContentView.swift` の以下を変更してください。

```swift
static let liffURLString = "http://localhost:8080/liff/default"
```

実機からアクセスできる URL（本番または公開ステージング）を設定してください。

## 起動手順

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
