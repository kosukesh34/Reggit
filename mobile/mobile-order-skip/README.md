# MobileOrderApp (Skip)

このディレクトリは、`Skip + SwiftUI` で作成した iOS / Android 向け WebView ラッパーです。

## 実装方針

- 画面本体はすべて `WebView`
- ネイティブ UI は最小化し、WebView を全画面表示
- 表示タブは `Skip.env` の `LIFF_FIXED_TAB`（または iOS/Android 個別設定）に固定
- LIFF 側のタブは非表示・遷移ロック（固定タブのみ表示）
- 起動時に `window.__MOBILE_DEVICE_INFO__` へ端末情報を注入し、`/api/devices/register` へ送信
- `window.__nativeSetPushToken(token)` を呼ぶと push token を更新して再登録
- **OneSignal**: プッシュ通知は OneSignal で送信。アプリ側で取得した subscription ID を上記でバックエンドに送り、お知らせ配信時に OneSignal API で送信します。

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

### AWS（本番）向けに接続して起動する

接続先を **AWS EB**（ngrok ではなく）にして iPad や実機で動作確認する手順です。

1. **接続先を AWS EB にしておく**  
   `Skip.env` の `LIFF_BASE_URL` / `LIFF_BASE_URL_IOS` が **http://Reggit.ap-northeast-3.elasticbeanstalk.com** になっていることを確認。独自ドメイン運用時は `https://reggit.shop` に変更可。

2. **パッケージ解決**
   ```bash
   cd mobile/mobile-order-skip
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
   - アプリ起動後、WebView が `LIFF_BASE_URL`（http://Reggit.ap-northeast-3.elasticbeanstalk.com）の LIFF を表示する。
   - 商品一覧・注文・会員などが表示されれば AWS 接続は成功。

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

APK はプロジェクトルート（mobile-order-skip）から次の場所に出力されます。

```
.build/Android/app/outputs/apk/debug/app-debug.apk
```

**4. エミュレータ／実機にインストールして起動**

```bash
# プロジェクトルート（mobile-order-skip）で実行。エミュレータまたは実機を 1 台だけ接続した状態で
adb install -r .build/Android/app/outputs/apk/debug/app-debug.apk
adb shell am start -n com.reggit.default/mobile.order.app.MainActivity
```

`BUILT_PRODUCTS_DIR` の実際のパスは、Xcode でビルドしたあと `~/Library/Developer/Xcode/DerivedData/` 内の `Project-` で始まるフォルダを確認してください。

### OneSignal プッシュ通知

- **App ID**: `Skip.env` の `ONE_SIGNAL_APP_ID` に OneSignal ダッシュボードの App ID（UUID）を設定済みです。iOS では `Darwin/Info.plist` の `OneSignalAppId` にも同じ値を入れてあります（App ID を変えた場合は両方揃える）。
- **バックエンド**: プロジェクトルートの `.env` に `PUSH_ONESIGNAL_ENABLED=true`, `PUSH_ONESIGNAL_APP_ID`, `PUSH_ONESIGNAL_API_KEY` を設定し、お知らせ配信で OneSignal が使われます。
- **iOS**: OneSignal SDK は Xcode の Swift Package で追加済み。
  - **Push Notifications 権限**: `Darwin/Entitlements.plist` に `aps-environment`（development / production）を設定済み。実機で「Apple iOS (APNs)」の購読者として登録されるために必須です。
  - **APNs の設定**: OneSignal ダッシュボードの「Settings → Platforms → Apple iOS (APNs)」で p8 キー（推奨）または証明書を設定してください。未設定だと iOS 購読者が 0 のままになります。
- **Android**: OneSignal は FCM を使用します。`Android/app/google-services.json` の **package_name** と、アプリの **applicationId** を一致させてください。
  - 現在の `google-services.json` は `com.reggit.default` 用です。このパッケージでビルドする場合は `Skip.env` の `ANDROID_APPLICATION_ID=com.reggit.default` と `PRODUCT_BUNDLE_IDENTIFIER=com.reggit.default` をそのまま使います。
  - 別の applicationId でビルドする場合は、Firebase コンソールでそのパッケージを追加し、ダウンロードした `google-services.json` で差し替えてください。

### プッシュ通知のテスト手順（実際に通知が届くか確認する）

1. **アプリをビルド・起動**
   - プロジェクトルートで: `./scripts/build-and-install-mobile.sh`
   - または Xcode で「MobileOrderApp App」を実機／シミュレータに Run、Android の場合は実機／エミュレータに APK をインストールして起動。

2. **通知を許可**
   - 初回起動時（または権限リセット後）に OneSignal の通知許可ダイアログが出たら「許可」をタップ。
   - **iOS**: 実機でのみプッシュが届きます。シミュレータでは通知は届きません。
   - **Android**: エミュレータ・実機どちらでも FCM 経由で届きます（Google Play 入りイメージ推奨）。

3. **登録完了を待つ**
   - 許可後、アプリが OneSignal に端末を登録するまで数秒〜数十秒かかることがあります。アプリを開いたまま少し待つか、一度バックグラウンドにしてから再度開くと登録されやすいです。

4. **テスト通知を送信**
   - プロジェクトルートで（Docker 利用時）:
     ```bash
     docker compose exec app php artisan push:onesignal-test --title="テスト通知" --body="確認用です" --force
     ```
   - ローカルで PHP 8.2 以上がある場合:
     ```bash
     php artisan push:onesignal-test --title="テスト通知" --body="確認用です" --force
     ```
   - 出力の「配信先: N 件」が 1 以上なら送信成功。端末に通知が表示されるはずです。

5. **届かない場合**
   - OneSignal ダッシュボードの「Audience → Subscriptions」で端末が 1 件以上表示されているか確認。「Apple iOS (APNs)」が 0 の場合は iOS 側の設定を確認。
   - **iOS**: 下記「Apple iOS (APNs) が 0 のとき」を参照。
   - `.env` の `PUSH_ONESIGNAL_APP_ID` が `Skip.env` の `ONE_SIGNAL_APP_ID` と同一か確認。
   - Android は `google-services.json` の package と `Skip.env` の `ANDROID_APPLICATION_ID` が一致しているか確認。

### 「Apple iOS (APNs) が 0 人」と表示されるとき

OneSignal の「Test for Subscribed Users」で「none specifically for the Apple iOS (APNs) platform」と出る場合は、次を順に確認してください。

1. **実機で実行しているか**
   - シミュレータでは APNs トークンが発行されないため、必ず実機でビルド・実行してください。

2. **Xcode で Push Notifications が有効か**
   - プロジェクトに **Push Notifications** の capability を追加済みです（`project.pbxproj` の `SystemCapabilities`）。
   - Xcode で `mobile/mobile-order-skip/Darwin/MobileOrderApp.xcodeproj` を開く。
   - 左の **TARGETS** から **MobileOrderApp**（または **MobileOrderApp App**）を選択 → **Signing & Capabilities** タブ。
   - **Push Notifications** が一覧に表示されていれば有効です。無い場合は **+ Capability** → **Push Notifications** を追加する。
   - `Darwin/Entitlements.plist` に `aps-environment` が入っていることも確認する。

3. **Apple Developer で App ID に Push を有効にしたか**（「Push notifications are not enabled for this app」と出る場合）
   - [Apple Developer](https://developer.apple.com/account) → **Certificates, Identifiers & Profiles** → **Identifiers**。
   - 対象の **App ID**（例: `com.reggit.default`）を選択。
   - **Push Notifications** にチェックを入れ、**Save** する。
   - Xcode で **Automatically manage signing** を使っている場合は、プロビジョニングプロファイルが自動更新される。使っていない場合は、該当プロファイルを再生成する。

4. **OneSignal ダッシュボードで APNs を設定しているか**
   - OneSignal ダッシュボード → **Settings** → **Platforms** → **Apple iOS (APNs)**。
   - **p8 Auth Key**（推奨）または **p12 Certificate** を未設定のままにしない。未設定だと端末が登録されても「Apple iOS (APNs)」としてカウントされません。

5. **通知を許可し、アプリを開いたまま待つ**
   - 実機でアプリ起動 → 通知許可ダイアログで「許可」をタップ。
   - アプリを**開いたまま** 15〜20 秒待ってから、OneSignal の **Audience → Subscriptions** を再読み込みする。

6. **クリーンビルドして再実行**
   - Xcode で **Product → Clean Build Folder** のあと、実機で再度 **Run** する。

### p8 を設定したのにプッシュが送れないとき

OneSignal に p8 を登録済みなのに通知が届かない場合は、次を確認してください。

1. **Bundle ID の一致（最も多い原因）**
   - アプリの **Bundle ID** と、OneSignal の **Apple iOS (APNs)** で p8 を設定したときの **Bundle ID** が**完全に同一**である必要があります。
   - このプロジェクトの Bundle ID は **Skip.env** の `PRODUCT_BUNDLE_IDENTIFIER` で決まります（例: `com.reggit.default`）。
   - OneSignal ダッシュボード → **Settings** → **Platforms** → **Apple iOS (APNs)** を開き、**Bundle ID** が上記と 1 文字も違わないか確認する。
   - Xcode で実際の値を確認する: ターゲット **MobileOrderApp App** → **General** → **Bundle Identifier**。

2. **Apple Developer の App ID**
   - その Bundle ID の **App ID** が Apple Developer の **Identifiers** に存在し、**Push Notifications** がオンになっているか確認する。

3. **送信先が 0 件になっていないか**
   - 送信時に「配信先: 0 件」の場合は、**Audience → Subscriptions** で「Apple iOS (APNs)」が 1 以上あるか確認する。0 の場合は端末がまだ OneSignal に登録されていないので、上記「Apple iOS (APNs) が 0 のとき」の手順を行う。

4. **開発環境と p8 の環境**
   - 実機で **Debug ビルド**で動かしている場合は、APNs は **development**（Sandbox）を使います。p8 は Sandbox & Production 両方に対応しているので、通常はそのままで問題ありません。**Archive（配布）** の場合は **production** になります。

### Android で画面が真っ白な場合

- **ネットワーク**: エミュレータ／実機から `LIFF_BASE_URL`（例: ngrok の URL）に到達できるか確認してください。エミュレータのブラウザで同じ URL を開いてみます。
- **ログ確認**: `adb logcat` で WebView や SSL 関連のエラーが出ていないか確認します。
- **URL 設定**: `Skip.env` の `LIFF_BASE_URL` がプロジェクトの `.env` の `APP_URL` と一致しているか確認し、必要なら `./scripts/sync-liff-url-from-env.sh` で同期してから再ビルドします。
