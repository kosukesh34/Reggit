#!/usr/bin/env bash
# .env の APP_URL を mobile/mobile-order-skip/Skip.env の LIFF_BASE_URL に同期する。
# プロジェクトルートで実行: ./scripts/sync-liff-url-from-env.sh

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
ENV_FILE="$ROOT_DIR/.env"
SKIP_ENV="$ROOT_DIR/mobile/mobile-order-skip/Skip.env"

if [ ! -f "$ENV_FILE" ]; then
  echo "Error: .env not found at $ENV_FILE"
  exit 1
fi
if [ ! -f "$SKIP_ENV" ]; then
  echo "Error: Skip.env not found at $SKIP_ENV"
  exit 1
fi

# APP_URL を取得（引用符は除去）
APP_URL=$(grep -E '^APP_URL=' "$ENV_FILE" | cut -d= -f2- | sed -e 's/^["'\'']//' -e 's/["'\'']$//' | tr -d '\r')
if [ -z "$APP_URL" ]; then
  echo "Error: APP_URL not found in .env"
  exit 1
fi

# Skip.env の LIFF_BASE_URL 行を更新（存在しなければ追加）
if grep -q '^LIFF_BASE_URL' "$SKIP_ENV"; then
  if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' "s|^LIFF_BASE_URL = .*|LIFF_BASE_URL = $APP_URL|" "$SKIP_ENV"
  else
    sed -i "s|^LIFF_BASE_URL = .*|LIFF_BASE_URL = $APP_URL|" "$SKIP_ENV"
  fi
else
  echo "" >> "$SKIP_ENV"
  echo "LIFF_BASE_URL = $APP_URL" >> "$SKIP_ENV"
fi

echo "Synced LIFF_BASE_URL = $APP_URL from .env to Skip.env"
