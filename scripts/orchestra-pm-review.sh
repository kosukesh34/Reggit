#!/usr/bin/env bash
# CEO/PM 戦略レビュー — cursor-agent で優先度を更新（実装はしない）
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LOG_DIR="$ROOT/.takt/daemon"
LOG="$LOG_DIR/pm-review.log"
LOCK="$LOG_DIR/pm-review.lock"

mkdir -p "$LOG_DIR"
export PATH="$HOME/.local/bin:/opt/homebrew/bin:/usr/local/bin:$PATH"
cd "$ROOT"

log() {
  echo "[$(date -Iseconds)] pm-review: $*" | tee -a "$LOG"
}

if ! agent status 2>&1 | grep -q "Logged in"; then
  log "cursor-agent 未ログイン — スキップ"
  exit 0
fi

if [[ -f "$LOCK" ]]; then
  pid="$(cat "$LOCK" 2>/dev/null || true)"
  if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then
    log "前回レビュー実行中 (pid=$pid) — スキップ"
    exit 0
  fi
fi

echo $$ > "$LOCK"
trap 'rm -f "$LOCK"' EXIT

log "開始"

PROMPT='mode: scheduled — PM strategy review for Reggit.

Follow .cursor/skills/pm-strategy-review/SKILL.md exactly.

Rules:
- You are CEO/PM only. Do NOT implement features or open large code changes.
- Update _documentation/PM_PRIORITIES.md with current ranked backlog.
- Read .takt/tasks.yaml and open GitHub issues/PRs before deciding.
- End with a 3-line summary of what changed in priorities.
- Use Japanese for PM_PRIORITIES.md content.'

if agent -p --trust --force --workspace "$ROOT" "$PROMPT" >> "$LOG" 2>&1; then
  log "完了"
else
  log "失敗 (exit $?)"
  exit 1
fi
