#!/usr/bin/env bash
# worktree に最新の .cursor 設定を同期（cursor-agent schema エラー防止）
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WT_ROOT="${HOME}/takt-worktrees"
BOOTSTRAP="$ROOT/.takt/bootstrap/cursor"

canonical_cli() {
  if [[ -s "$BOOTSTRAP/cli.json" ]]; then
    echo "$BOOTSTRAP/cli.json"
  elif [[ -s "$ROOT/.cursor/cli.json" ]]; then
    echo "$ROOT/.cursor/cli.json"
  fi
}

sync_dir() {
  local target="$1"
  [[ -d "$target" ]] || return 0
  mkdir -p "$target/.cursor"
  local cli_src
  cli_src="$(canonical_cli)"
  if [[ -n "$cli_src" ]]; then
    cat "$cli_src" > "$target/.cursor/cli.json"
  fi
  for f in permissions.json hooks.json; do
    if [[ -f "$ROOT/.cursor/$f" ]]; then
      cat "$ROOT/.cursor/$f" > "$target/.cursor/$f"
    fi
  done
  if [[ -d "$ROOT/.cursor/hooks" ]]; then
    mkdir -p "$target/.cursor/hooks"
    for hook in "$ROOT/.cursor/hooks/"*; do
      [[ -f "$hook" ]] && cat "$hook" > "$target/.cursor/hooks/$(basename "$hook")"
    done
  fi
}

# プロジェクトルートは bootstrap を正とする（自己上書き防止）
if [[ -s "$BOOTSTRAP/cli.json" ]] && [[ "$(cd "$ROOT" && pwd)" == "$ROOT" ]]; then
  cat "$BOOTSTRAP/cli.json" > "$ROOT/.cursor/cli.json"
fi

if [[ -d "$WT_ROOT" ]]; then
  for dir in "$WT_ROOT"/*; do
    sync_dir "$dir"
  done
fi
