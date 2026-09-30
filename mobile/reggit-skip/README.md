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

## 接続設定

接続先・プロジェクトキー・初期タブは `Sources/Reggit/Domain/Models/MobileConnectionEnvironment.swift` の1箇所で管理します。

- **Debug**: 開発環境（設定した ngrok URL と開発用プロジェクトキー）
- **Release**: 本番環境
- iOS はビルド構成、Android は `BuildConfig.DEBUG` に従って自動的に切り替わります。
- `Info.plist` / `Skip.env` / Gradle に URL やプロジェクトキーを転記する必要はありません。Android の通知取消しAPIも同じ共通設定を使用します。
- 店舗選択の保存値は開発・本番で分離されます。

開発 URL を変更するときは、このファイルの `.development` の `baseURL` を変更するか、Laravel の `.env` の `APP_URL` を更新して `./scripts/sync-liff-url-from-env.sh` を実行し、再ビルドしてください。本番設定には影響しません。開発の `projectKey` も同じファイルで指定します。

本番に接続するには Xcode の **Edit Scheme → Run → Build Configuration** を **Release** にするか、Android で `./gradlew :app:assembleRelease` を実行します。開発に戻すときは **Debug** に切り替えます。

初期タブは同じファイルの `fixedTab` で指定します。許可タブキー: `home`, `products`, `orders`, `stamps`, `coupons`, `queue`, `member`, `reservations`, `gallery`。

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

Release ビルドで Android も更新する場合は、`Reggit.xcconfig` の `SKIP_ACTION = none` をコマンド引数で上書きしてください。`skip app launch --android` だけでは設定により Android のビルドが省略され、古い APK が残ることがあります。

```bash
cd mobile/reggit-skip
xcodebuild -workspace Project.xcworkspace -scheme "Reggit App" -configuration Release -destination "platform=iOS Simulator,name=iPhone 16e" SKIP_ACTION=build CODE_SIGNING_ALLOWED=YES CODE_SIGN_IDENTITY=- build
adb install -r .build/Android/app/outputs/apk/release/app-release.apk
adb shell monkey -p com.reggit.default -c android.intent.category.LAUNCHER 1
```

Release の最適化で Activity 名が変わる場合があるため、起動は固定 Activity 名の `launchRelease` ではなくランチャー Intent を使います。

iOS のプッシュ受信を検証する場合は `CODE_SIGNING_ALLOWED=NO` を使わず、Xcode にシミュレーター用の APNs entitlement を生成させてください。FCM の送信受付成功と端末での受信成功は別に確認します。

## プッシュ（FCM）

- **iOS**: `GoogleService-Info.plist` + Firebase Messaging。APNs `.p8` は Firebase Console に登録。
- **Android**: `google-services.json` + FCM。トークンは SharedPreferences（Skip UserDefaults）へ保存。

## トラブルシュート

- **ログ確認**: iOS は Xcode コンソール、Android は `adb logcat`。
- **URL 設定**: `MobileConnectionEnvironment.swift` の開発 `baseURL` が `.env` の `APP_URL` と一致しているか確認。
