#!/usr/bin/env bash
# Reggit Takt ローカルダッシュボード — http://127.0.0.1:8765
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PORT="${TAKT_DASHBOARD_PORT:-8765}"
URL="http://127.0.0.1:${PORT}/"

cd "$ROOT"
chmod +x "$ROOT/scripts/takt-dashboard-server.py"

if ! python3 -c "import yaml" 2>/dev/null; then
  echo "PyYAML が必要です: pip3 install pyyaml" >&2
  exit 1
fi

# 既に起動中ならブラウザだけ開く
if curl -sf "${URL}api/status" >/dev/null 2>&1; then
  echo "ダッシュボードは既に起動中: $URL"
else
  echo "ダッシュボードを起動: $URL"
  nohup python3 "$ROOT/scripts/takt-dashboard-server.py" >> "$ROOT/.takt/daemon/dashboard.log" 2>&1 &
  disown
  for _ in $(seq 1 20); do
    if curl -sf "${URL}api/status" >/dev/null 2>&1; then
      break
    fi
    sleep 0.25
  done
fi

if command -v open >/dev/null 2>&1; then
  open "$URL"
else
  echo "ブラウザで開いてください: $URL"
fi
