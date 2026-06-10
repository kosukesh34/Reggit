#!/usr/bin/env bash
# Takt 自律実行ウォッチャー — タスクがキューに入ると自動実行（常駐）
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
export PATH="$HOME/.local/bin:/opt/homebrew/bin:/usr/local/bin:$PATH"

mkdir -p "$ROOT/.takt/daemon"

if ! agent status 2>&1 | grep -q "Logged in"; then
  echo "[$(date -Iseconds)] cursor-agent 未ログイン — agent login が必要です" >> "$ROOT/.takt/daemon/stderr.log"
  exit 1
fi

# Reggit アプリ（Docker）を起動状態に保つ
if command -v docker >/dev/null 2>&1; then
  docker compose up -d >> "$ROOT/.takt/daemon/stdout.log" 2>> "$ROOT/.takt/daemon/stderr.log" || true
fi

# worktree へ .cursor 設定を同期
if [[ -x "$ROOT/scripts/takt-sync-cursor-config.sh" ]]; then
  "$ROOT/scripts/takt-sync-cursor-config.sh" || true
fi

# pending タスクに GitHub Issue がなければ自動作成
if [[ -x "$ROOT/scripts/takt-ensure-task-issues.sh" ]]; then
  "$ROOT/scripts/takt-ensure-task-issues.sh" || true
fi

# 新規 worktree 作成直後に .cursor を同期（clone 直後の agent 起動前を狙う）
(
  while true; do
    "$ROOT/scripts/takt-sync-cursor-config.sh" 2>/dev/null || true
    sleep 1
  done
) &
SYNC_PID=$!
trap 'kill "$SYNC_PID" 2>/dev/null || true' EXIT

echo "[$(date -Iseconds)] Takt watch 開始" >> "$ROOT/.takt/daemon/stdout.log"
exec takt watch >> "$ROOT/.takt/daemon/stdout.log" 2>> "$ROOT/.takt/daemon/stderr.log"
