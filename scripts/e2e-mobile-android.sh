#!/usr/bin/env bash
# Android エミュレータ上で Maestro フロー（起動 + Google Wallet）を実行する。
# 使い方（リポジトリルート）:
#   ./scripts/e2e-mobile-android.sh
#   ./scripts/e2e-mobile-android.sh wallet
#   ./scripts/e2e-mobile-android.sh launch
#
# アプリ未インストール時は既存 APK があれば adb install を試す。
# APK が無い場合は先に ./scripts/build-and-install-mobile.sh を実行する。

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FLOWS_DIR="${REPO_ROOT}/e2e/mobile/flows"
APK_PATH="${REPO_ROOT}/mobile/reggit-skip/.build/Android/app/outputs/apk/debug/app-debug.apk"
APP_ID="com.reggit.default"
TARGET="${1:-}"

if ! command -v maestro >/dev/null 2>&1; then
  echo "maestro が見つかりません。https://maestro.mobile.dev を参照して CLI を入れてください。" >&2
  exit 1
fi

if ! command -v adb >/dev/null 2>&1; then
  echo "adb が見つかりません。Android SDK platform-tools を PATH に追加してください。" >&2
  exit 1
fi

echo "== adb devices =="
adb devices

DEVICE_COUNT="$(adb devices | awk 'NR>1 && $2=="device" { count++ } END { print count+0 }')"
if [[ "${DEVICE_COUNT}" -lt 1 ]]; then
  echo "接続中の Android デバイス/エミュレータがありません。" >&2
  exit 1
fi

ensure_app_installed() {
  if adb shell pm path "${APP_ID}" >/dev/null 2>&1; then
    echo "== アプリ確認: ${APP_ID} インストール済み =="
    return 0
  fi

  echo "== アプリ未インストール: ${APP_ID} =="
  if [[ ! -f "${APK_PATH}" ]]; then
    echo "APK がありません: ${APK_PATH}" >&2
    echo "先に実行してください: ./scripts/build-and-install-mobile.sh" >&2
    exit 1
  fi

  echo "== adb install: ${APK_PATH} =="
  if ! adb install -r "${APK_PATH}"; then
    echo "" >&2
    echo "APK のインストールに失敗しました。" >&2
    echo "よくある原因: エミュレータの空き容量不足 (INSTALL_FAILED_INSUFFICIENT_STORAGE)" >&2
    echo "対処例:" >&2
    echo "  1. Android Studio Device Manager で AVD の Wipe Data" >&2
    echo "  2. または不要アプリを削除してから再実行" >&2
    echo "  3. 空き確認: adb shell df -h /data" >&2
    exit 1
  fi
}

ensure_app_installed

if [[ "${TARGET}" == "wallet" ]]; then
  FLOW_PATH="${FLOWS_DIR}/wallet-google.yaml"
elif [[ "${TARGET}" == "launch" ]]; then
  FLOW_PATH="${FLOWS_DIR}/launch.yaml"
else
  FLOW_PATH="${FLOWS_DIR}"
fi

echo "== Maestro: ${FLOW_PATH} =="
maestro test "${FLOW_PATH}"
