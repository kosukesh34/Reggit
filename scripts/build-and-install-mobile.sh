#!/usr/bin/env bash
# iOS と Android の両方をビルドし、シミュレータ／エミュレータにインストールするスクリプト。
# プロジェクトルートから実行: ./scripts/build-and-install-mobile.sh

set -e

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MOBILE_DIR="${REPO_ROOT}/mobile/reggit-skip"
APK_PATH="${MOBILE_DIR}/.build/Android/app/outputs/apk/debug/app-debug.apk"
# iOS: Skip.env の PRODUCT_BUNDLE_IDENTIFIER を優先
BUNDLE_ID="com.reggit.default"
if [[ -f "${MOBILE_DIR}/Skip.env" ]]; then
  _line=$(grep -E "^PRODUCT_BUNDLE_IDENTIFIER[ =]" "${MOBILE_DIR}/Skip.env" 2>/dev/null | head -1)
  if [[ -n "$_line" ]]; then
    _val=$(echo "$_line" | sed 's/^PRODUCT_BUNDLE_IDENTIFIER[= ]*//;s/^[ "]*//;s/[ "]*$//;s/\r//')
    [[ -n "$_val" ]] && BUNDLE_ID="$_val"
  fi
fi
# Android: Skip.env の ANDROID_APPLICATION_ID を読み、起動コマンドで使用
_android_app_id="jp.gamechanger.mobileorder"
if [[ -f "${MOBILE_DIR}/Skip.env" ]]; then
  _line=$(grep -E "^ANDROID_APPLICATION_ID[ =]" "${MOBILE_DIR}/Skip.env" 2>/dev/null | head -1)
  if [[ -n "$_line" ]]; then
    _val=$(echo "$_line" | sed 's/^ANDROID_APPLICATION_ID[= ]*//;s/^[ "]*//;s/[ "]*$//;s/\r//')
    [[ -n "$_val" ]] && _android_app_id="$_val"
  fi
fi
ANDROID_APP_ID="$_android_app_id"
ANDROID_ACTIVITY="${ANDROID_APP_ID}/reggit.app.MainActivity"

# iOS のビルド先（DESTINATION 未指定時は利用可能な iPhone Simulator を自動選択）
if [[ -n "${DESTINATION:-}" ]]; then
  IOS_DESTINATION="${DESTINATION}"
else
  IOS_DESTINATION="$(python3 - <<'PY'
import json, subprocess, sys
raw = subprocess.check_output(["xcrun", "simctl", "list", "devices", "-j"], text=True)
data = json.loads(raw)
preferred = ["iPhone 17", "iPhone 17 Pro", "iPhone 16e", "iPhone Air", "iPhone 16 Pro", "iPhone 16"]
candidates = []
for runtime, devices in data.get("devices", {}).items():
    if "iOS" not in runtime:
        continue
    for dev in devices:
        if not dev.get("isAvailable", True):
            continue
        name = dev.get("name") or ""
        if not name.startswith("iPhone"):
            continue
        os_ver = runtime.rsplit(".iOS-", 1)[-1].replace("-", ".") if ".iOS-" in runtime else ""
        candidates.append((name, os_ver))
if not candidates:
    print("platform=iOS Simulator,name=iPhone 17")
    sys.exit(0)
def rank(item):
    name, os_ver = item
    try:
        pref = preferred.index(name)
    except ValueError:
        pref = 100
    return (pref, name, os_ver)
name, os_ver = sorted(candidates, key=rank)[0]
print(f"platform=iOS Simulator,name={name},OS={os_ver}" if os_ver else f"platform=iOS Simulator,name={name}")
PY
)"
fi
echo "iOS DESTINATION=${IOS_DESTINATION}"

echo "== Reggit: ビルド＆インストール =="
cd "$MOBILE_DIR"

echo ""
echo "--- 0. Skip artifact bundle（xcodebuild 用）---"
# xcodebuild が skipstone プラグインで参照する skip.artifactbundle を DerivedData に配置
DERIVED_DATA="${HOME}/Library/Developer/Xcode/DerivedData"
PROJECT_DIR=$(find "$DERIVED_DATA" -maxdepth 1 -type d -name "Project-*" 2>/dev/null | head -1)
if [[ -z "$PROJECT_DIR" ]]; then
  PROJECT_DIR="${DERIVED_DATA}/Project-chmyacsiarinkyekzpihcwqjmhyn"
fi
ARTIFACT_DIR="${PROJECT_DIR}/SourcePackages/artifacts/skip/skip"
SKIP_BUNDLE=$(find /opt/homebrew/Caskroom/skip /usr/local/Caskroom/skip -type d -name 'skip.artifactbundle' 2>/dev/null | sort -V | tail -1 || true)
if [[ -n "${SKIP_BUNDLE}" && -d "${SKIP_BUNDLE}" ]]; then
  mkdir -p "$ARTIFACT_DIR"
  ln -sfn "$SKIP_BUNDLE" "$ARTIFACT_DIR/skip.artifactbundle" 2>/dev/null || true
  echo "Skip artifact: $ARTIFACT_DIR/skip.artifactbundle -> $SKIP_BUNDLE"
fi

echo ""
echo "--- 1. 共通準備 ---"
swift package resolve

echo ""
echo "--- 2. iOS ビルド ---"
xcodebuild -workspace Project.xcworkspace -scheme "Reggit App" \
  -destination "$IOS_DESTINATION" \
  -configuration Debug -skipPackagePluginValidation build

APP_PATH=$(find ~/Library/Developer/Xcode/DerivedData -name "Reggit.app" -path "*Debug-iphonesimulator*" -type d 2>/dev/null | head -1)
BUILT_PRODUCTS_DIR=$(dirname "$APP_PATH")
if [[ -z "$APP_PATH" || ! -d "$APP_PATH" ]]; then
  echo "Error: Reggit.app が見つかりません"
  exit 1
fi
echo "iOS ビルド成果物: $APP_PATH"

echo ""
echo "--- 3. iOS インストール・起動 ---"
BOOTED_UDID=$(xcrun simctl list devices -j 2>/dev/null | grep -B1 '"state" : "Booted"' | grep '"udid"' | head -1 | sed 's/.*: *"\([^"]*\)".*/\1/')
if [[ -z "$BOOTED_UDID" ]]; then
  # 起動中シミュレータがなければ DESTINATION からデバイス名を取得し、UDID で起動する
  SIM_NAME=$(echo "$IOS_DESTINATION" | sed -n 's/.*name=\([^,)]*\).*/\1/p' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
  [[ -z "$SIM_NAME" ]] && SIM_NAME="iPhone 16 Pro"
  echo "シミュレータが起動していないため、「${SIM_NAME}」を起動します..."
  # 名前で起動を試行
  xcrun simctl boot "$SIM_NAME" 2>/dev/null || true
  sleep 2
  BOOTED_UDID=$(xcrun simctl list devices -j 2>/dev/null | grep -B1 '"state" : "Booted"' | grep '"udid"' | head -1 | sed 's/.*: *"\([^"]*\)".*/\1/')
  if [[ -z "$BOOTED_UDID" ]]; then
    # 名前で起動できなかった場合は available なデバイスから UDID を取得して起動
    SIM_UDID=$(xcrun simctl list devices -j 2>/dev/null | python3 -c "
import json, sys
name = '''$SIM_NAME'''
try:
    d = json.load(sys.stdin)
    for runtime, devices in d.get('devices', {}).items():
        for dev in devices:
            if dev.get('name') == name and dev.get('isAvailable', True):
                print(dev.get('udid', ''))
                sys.exit(0)
except Exception:
    pass
" 2>/dev/null)
    if [[ -n "$SIM_UDID" ]]; then
      xcrun simctl boot "$SIM_UDID" 2>/dev/null && BOOTED_UDID="$SIM_UDID"
      sleep 3
    fi
  fi
  [[ -z "$BOOTED_UDID" ]] && BOOTED_UDID=$(xcrun simctl list devices -j 2>/dev/null | grep -B1 '"state" : "Booted"' | grep '"udid"' | head -1 | sed 's/.*: *"\([^"]*\)".*/\1/')
fi
if [[ -n "$BOOTED_UDID" ]]; then
  xcrun simctl install "$BOOTED_UDID" "$APP_PATH"
  xcrun simctl launch "$BOOTED_UDID" "$BUNDLE_ID"
  echo "iOS: シミュレータにインストールして起動しました (UDID: $BOOTED_UDID)"
  open -a Simulator 2>/dev/null || true
else
  echo "シミュレータを起動できませんでした。Xcode でシミュレータを起動してから再度実行するか、手動で:"
  echo "  xcrun simctl install \"iPhone 16 Pro\" \"$APP_PATH\""
  echo "  xcrun simctl launch \"iPhone 16 Pro\" $BUNDLE_ID"
fi

echo ""
echo "--- 4. Android ビルド ---"
export ANDROID_HOME="${ANDROID_HOME:-$HOME/Library/Android/sdk}"
export BUILT_PRODUCTS_DIR="$BUILT_PRODUCTS_DIR"
cd "$MOBILE_DIR/Android"
gradle assembleDebug
cd "$MOBILE_DIR"

if [[ ! -f "$APK_PATH" ]]; then
  echo "Error: APK が見つかりません: $APK_PATH"
  exit 1
fi
echo "Android APK: $APK_PATH"

echo ""
echo "--- 5. Android インストール・起動 ---"
DEVICE=$(adb devices 2>/dev/null | grep -E '^\S+\s+device$' | head -1 | awk '{print $1}')
STARTED_EMU=""
if [[ -z "$DEVICE" ]]; then
  echo "Android デバイスが接続されていません。エミュレータを起動します..."
  EMU_CMD="${ANDROID_HOME:-$HOME/Library/Android/sdk}/emulator/emulator"
  [[ -x "$EMU_CMD" ]] || EMU_CMD="emulator"
  # 環境変数 ANDROID_AVD または Pixel_9 を優先（例: ANDROID_AVD=Pixel_9）
  AVDS=$("$EMU_CMD" -list-avds 2>/dev/null)
  EMU_AVD=""
  if [[ -n "${ANDROID_AVD:-}" ]]; then
    echo "$AVDS" | grep -qx "$ANDROID_AVD" && EMU_AVD="$ANDROID_AVD"
  fi
  if [[ -z "$EMU_AVD" ]]; then
    echo "$AVDS" | grep -q "Pixel_9" && EMU_AVD="Pixel_9"
  fi
  if [[ -z "$EMU_AVD" ]]; then
    EMU_AVD=$(echo "$AVDS" | head -1)
  fi
  if [[ -z "$EMU_AVD" ]]; then
    echo "利用可能な AVD がありません。Android Studio でエミュレータを作成してください。"
  else
    "$EMU_CMD" -avd "$EMU_AVD" -no-snapshot-load &
    echo "エミュレータ $EMU_AVD を起動中（起動完了まで待機）..."
    adb wait-for-device
    DEVICE=$(adb devices 2>/dev/null | grep -E '^\S+\s+device$' | head -1 | awk '{print $1}')
    echo "デバイス接続済み: $DEVICE。ブート完了を待機..."
    for i in $(seq 1 60); do
      BOOTED=$(adb -s "$DEVICE" shell getprop sys.boot_completed 2>/dev/null | tr -d '\r')
      [[ "$BOOTED" == "1" ]] && break
      sleep 2
    done
    STARTED_EMU=1
  fi
fi
DEVICE=$(adb devices 2>/dev/null | grep -E '^\S+\s+device$' | head -1 | awk '{print $1}')
if [[ -n "$DEVICE" ]]; then
  adb -s "$DEVICE" install -r "$APK_PATH"
  adb -s "$DEVICE" shell am start -n "$ANDROID_ACTIVITY"
  echo "Android: デバイス $DEVICE にインストールして起動しました"
  if [[ -n "$STARTED_EMU" ]] && [[ "$(uname)" == "Darwin" ]]; then
    echo "Android エミュレータの画面を前面に表示します..."
    osascript -e 'tell application "System Events" to set frontmost of first process whose name contains "qemu" or name contains "Emulator" or name contains "emulator" to true' 2>/dev/null || \
    open -a "Emulator" 2>/dev/null || true
  fi
else
  echo "Android デバイス／エミュレータに接続できませんでした。手動で:"
  echo "  adb install -r $APK_PATH"
  echo "  adb shell am start -n $ANDROID_ACTIVITY"
fi

echo ""
echo "== 完了 =="
