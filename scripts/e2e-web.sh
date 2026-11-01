#!/usr/bin/env bash
# Web E2E（Playwright）を開発用 DB・ログから切り離した使い捨て環境で実行する。
# 前提: make up 済み（db コンテナが起動中）。Playwright はパスキーの確認に localhost が必要なためホストで動かす。
# 引数はそのまま playwright test に渡す（例: scripts/e2e-web.sh customer-shell）。設定ファイルは E2E_CONFIG で切り替える。
set -euo pipefail
cd "$(dirname "$0")/.."

if [ ! -d node_modules/@playwright/test ]; then
  echo 'ホストに Playwright がありません。先に npm ci && npx playwright install chromium を実行してください。' >&2
  exit 1
fi

base_url=http://localhost:8090
compose=(docker compose --profile e2e)
mysql_root=(docker compose exec -T db mysql -uroot -proot)

cleanup() {
  "${compose[@]}" rm -sf e2e >/dev/null 2>&1 || true
  "${mysql_root[@]}" -e 'DROP DATABASE IF EXISTS reggit_e2e' 2>/dev/null || true
}
trap cleanup EXIT

"${mysql_root[@]}" -e "DROP DATABASE IF EXISTS reggit_e2e; CREATE DATABASE reggit_e2e; GRANT ALL PRIVILEGES ON reggit_e2e.* TO 'reggit_user'@'%';" 2>/dev/null
E2E_APP_KEY="base64:$(openssl rand -base64 32)" "${compose[@]}" up -d e2e
"${compose[@]}" exec -T e2e php artisan migrate --seed --force

for _ in $(seq 60); do curl -fs -o /dev/null "$base_url/health" && break; sleep 1; done
curl -fsS -o /dev/null "$base_url/health"
E2E_BASE_URL="$base_url" npx playwright test --config="${E2E_CONFIG:-e2e/web/playwright.config.ts}" "$@"
