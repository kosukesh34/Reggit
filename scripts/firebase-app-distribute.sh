#!/usr/bin/env bash
# Firebase App Distribution へビルドをアップロードする（プロジェクト: reggit-dbb5a）。
# 使い方:
#   ./scripts/firebase-app-distribute.sh skip-android path/to/app.apk [テスターメール,...]
#   ./scripts/firebase-app-distribute.sh skip-ios path/to/Reggit.ipa [テスターメール,...]
#   ./scripts/firebase-app-distribute.sh wait path/to/WaitingSystem.ipa [テスターメール,...]
#   ./scripts/firebase-app-distribute.sh kitchen path/to/KitchenMonitor.ipa [テスターメール,...]
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PROJECT_ID="reggit-dbb5a"
FIREBASE=(npx -y firebase-tools@latest)

usage() {
  sed -n '2,8p' "$0" | sed 's/^# \{0,1\}//'
  exit 1
}

[[ $# -ge 2 ]] || usage
APP_KEY="$1"
FILE="$2"
TESTERS="${3:-}"

case "$APP_KEY" in
  skip-android) APP_ID="1:456098361001:android:d70b996d7a7618bc7e03b8" ;;
  skip-ios)     APP_ID="1:456098361001:ios:5a1848bad8b197cf7e03b8" ;;
  wait)         APP_ID="1:456098361001:ios:ac13094f0e53487e7e03b8" ;;
  kitchen)      APP_ID="1:456098361001:ios:f0e1ebae0c850efa7e03b8" ;;
  *) echo "unknown app: $APP_KEY"; usage ;;
esac

if [[ ! -f "$FILE" ]]; then
  echo "file not found: $FILE" >&2
  exit 1
fi

ARGS=(appdistribution:distribute "$FILE" --app "$APP_ID" --project "$PROJECT_ID")
if [[ -n "$TESTERS" ]]; then
  ARGS+=(--testers "$TESTERS")
fi

cd "$ROOT"
"${FIREBASE[@]}" "${ARGS[@]}"
