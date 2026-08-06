#!/usr/bin/env bash
# GitHub Actions 向け: iOS Simulator で Maestro 起動スモークを実行する。
# Android+Wallet はローカル（./scripts/e2e-mobile-android.sh）向け。CI 必須は起動のみ。
# 使い方（リポジトリルート）: ./scripts/e2e-mobile-ios-ci.sh

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MOBILE_DIR="${REPO_ROOT}/mobile/reggit-skip"
FLOW_PATH="${REPO_ROOT}/e2e/mobile/flows/launch.yaml"
BUNDLE_ID="com.reggit.default"

if [[ -f "${MOBILE_DIR}/Skip.env" ]]; then
  _line=$(grep -E "^PRODUCT_BUNDLE_IDENTIFIER[ =]" "${MOBILE_DIR}/Skip.env" 2>/dev/null | head -1 || true)
  if [[ -n "${_line}" ]]; then
    _val=$(echo "$_line" | sed 's/^PRODUCT_BUNDLE_IDENTIFIER[= ]*//;s/^[ "]*//;s/[ "]*$//;s/\r//')
    [[ -n "$_val" ]] && BUNDLE_ID="$_val"
  fi
fi

if ! command -v maestro >/dev/null 2>&1; then
  echo "maestro が見つかりません。" >&2
  exit 1
fi

resolve_simulator() {
  python3 - <<'PY'
import json, os, subprocess, sys
preferred = ["iPhone 17", "iPhone 17 Pro", "iPhone 16e", "iPhone Air", "iPhone 16", "iPhone 16 Pro"]
raw = subprocess.check_output(["xcrun", "simctl", "list", "devices", "-j"], text=True)
data = json.loads(raw)
candidates = []
for runtime, devices in data.get("devices", {}).items():
    if "iOS" not in runtime:
        continue
    os_ver = runtime.rsplit(".iOS-", 1)[-1].replace("-", ".") if ".iOS-" in runtime else ""
    for dev in devices:
        if not dev.get("isAvailable", True):
            continue
        name = dev.get("name") or ""
        if not name.startswith("iPhone"):
            continue
        udid = dev.get("udid") or ""
        if not udid:
            continue
        candidates.append((name, os_ver, udid))

dest_env = os.environ.get("DESTINATION", "").strip()
if dest_env:
    # DESTINATION 指定時も UDID を名前から解決する
    want_name = ""
    for part in dest_env.split(","):
        part = part.strip()
        if part.startswith("name="):
            want_name = part[len("name="):]
    matched = [c for c in candidates if c[0] == want_name] if want_name else []
    if matched:
        name, os_ver, udid = matched[0]
        print(f"{dest_env}\t{udid}\t{name}")
        sys.exit(0)
    print(f"{dest_env}\t\t{want_name or 'unknown'}")
    sys.exit(0)

if not candidates:
    print("利用可能な iPhone Simulator がありません", file=sys.stderr)
    sys.exit(1)

def rank(item):
    name, os_ver, _ = item
    try:
        pref = preferred.index(name)
    except ValueError:
        pref = 100
    return (pref, name, os_ver)

name, os_ver, udid = sorted(candidates, key=rank)[0]
dest = f"platform=iOS Simulator,name={name},OS={os_ver}" if os_ver else f"platform=iOS Simulator,name={name}"
print(f"{dest}\t{udid}\t{name}")
PY
}

link_skip_artifact() {
  DERIVED_DATA="${HOME}/Library/Developer/Xcode/DerivedData"
  mkdir -p "${DERIVED_DATA}"
  PROJECT_DIR=$(find "$DERIVED_DATA" -maxdepth 1 -type d -name "Project-*" 2>/dev/null | head -1 || true)
  if [[ -z "${PROJECT_DIR}" ]]; then
    PROJECT_DIR="${DERIVED_DATA}/Project-ci"
  fi
  ARTIFACT_DIR="${PROJECT_DIR}/SourcePackages/artifacts/skip/skip"
  mkdir -p "${ARTIFACT_DIR}"

  SKIP_BUNDLE=$(find /opt/homebrew/Caskroom/skip /usr/local/Caskroom/skip -type d -name 'skip.artifactbundle' 2>/dev/null | sort -V | tail -1 || true)
  if [[ -n "${SKIP_BUNDLE}" && -d "${SKIP_BUNDLE}" ]]; then
    ln -sfn "${SKIP_BUNDLE}" "${ARTIFACT_DIR}/skip.artifactbundle"
    echo "Skip artifact: ${ARTIFACT_DIR}/skip.artifactbundle -> ${SKIP_BUNDLE}"
  else
    echo "Skip artifactbundle が見つかりません（brew install skiptools/skip/skip が必要）" >&2
  fi
}

echo "== resolve iOS simulator =="
RESOLVED="$(resolve_simulator)"
IOS_DESTINATION="$(echo "${RESOLVED}" | cut -f1)"
SIM_UDID="$(echo "${RESOLVED}" | cut -f2)"
SIM_NAME="$(echo "${RESOLVED}" | cut -f3)"
echo "DESTINATION=${IOS_DESTINATION}"
echo "SIM_UDID=${SIM_UDID}"
echo "SIM_NAME=${SIM_NAME}"

echo "== Skip artifact =="
link_skip_artifact

echo "== swift package resolve =="
cd "${MOBILE_DIR}"
swift package resolve

echo "== xcodebuild =="
xcodebuild -workspace Project.xcworkspace -scheme "Reggit App" \
  -destination "${IOS_DESTINATION}" \
  -configuration Debug -skipPackagePluginValidation build

APP_PATH=$(find "${HOME}/Library/Developer/Xcode/DerivedData" -name "Reggit.app" -path "*Debug-iphonesimulator*" -type d 2>/dev/null | head -1)
if [[ -z "${APP_PATH}" || ! -d "${APP_PATH}" ]]; then
  echo "Error: Reggit.app が見つかりません" >&2
  exit 1
fi
echo "APP_PATH=${APP_PATH}"

if [[ -z "${SIM_UDID}" ]]; then
  echo "シミュレータ UDID を解決できませんでした" >&2
  xcrun simctl list devices available | grep iPhone | head -20 >&2 || true
  exit 1
fi

echo "== boot simulator UDID=${SIM_UDID} =="
xcrun simctl boot "${SIM_UDID}" 2>/dev/null || true
for i in $(seq 1 60); do
  STATE=$(xcrun simctl list devices | grep "${SIM_UDID}" | grep -F "(Booted)" || true)
  if [[ -n "${STATE}" ]]; then
    break
  fi
  sleep 2
  xcrun simctl boot "${SIM_UDID}" 2>/dev/null || true
done

if ! xcrun simctl list devices | grep "${SIM_UDID}" | grep -q "(Booted)"; then
  echo "シミュレータを起動できませんでした: ${SIM_NAME} (${SIM_UDID})" >&2
  exit 1
fi

xcrun simctl install "${SIM_UDID}" "${APP_PATH}"
open -a Simulator 2>/dev/null || true

echo "== Maestro: ${FLOW_PATH} (appId=${BUNDLE_ID}) =="
maestro test "${FLOW_PATH}"
