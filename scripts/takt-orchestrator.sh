#!/usr/bin/env bash
# Issue/PR 巡回ループ（auto-improvement-loop）— 実装 watch とは別プロセスで常駐
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
export PATH="$HOME/.local/bin:/opt/homebrew/bin:/usr/local/bin:$PATH"
LOG="$ROOT/.takt/daemon/orchestrator.log"
ORDER="$ROOT/.takt/tasks/autonomous-loop/order.md"

mkdir -p "$ROOT/.takt/daemon"

log() {
  echo "[$(date -Iseconds)] orchestrator: $*" | tee -a "$LOG"
}

if ! agent status 2>&1 | grep -q "Logged in"; then
  log "cursor-agent 未ログイン — agent login が必要です"
  exit 1
fi

if [[ ! -f "$ORDER" ]]; then
  log "order.md が見つかりません: $ORDER"
  exit 1
fi

if command -v docker >/dev/null 2>&1; then
  docker compose up -d >> "$LOG" 2>&1 || true
fi

if [[ -x "$ROOT/scripts/takt-sync-cursor-config.sh" ]]; then
  "$ROOT/scripts/takt-sync-cursor-config.sh" >> "$LOG" 2>&1 || true
fi

log "開始（auto-improvement-loop）"

while true; do
  if takt -w auto-improvement-loop "$(cat "$ORDER")" >> "$LOG" 2>&1; then
    log "1 サイクル終了 — 30 秒後に再起動"
    sleep 30
  else
    code=$?
    log "終了コード $code — 60 秒後に再起動"
    sleep 60
  fi
done
