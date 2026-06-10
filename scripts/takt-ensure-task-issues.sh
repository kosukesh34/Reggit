#!/usr/bin/env bash
# pending タスクに GitHub Issue が未紐づけなら自動作成（または order.md 内の親 Issue へリンク）
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TASKS_FILE="$ROOT/.takt/tasks.yaml"
LOG="$ROOT/.takt/daemon/stdout.log"

log() {
  echo "[$(date -Iseconds)] ensure-task-issues: $*" >> "$LOG"
}

if [[ ! -f "$TASKS_FILE" ]]; then
  exit 0
fi

if ! command -v gh >/dev/null 2>&1; then
  log "gh CLI 未インストール — スキップ"
  exit 0
fi

export PATH="$HOME/.local/bin:/opt/homebrew/bin:/usr/local/bin:$PATH"
cd "$ROOT"

RESULT="$(python3 - "$ROOT" "$TASKS_FILE" <<'PY'
import re
import subprocess
import sys
from pathlib import Path

root = Path(sys.argv[1])
tasks_file = Path(sys.argv[2])
text = tasks_file.read_text(encoding="utf-8")

if not text.strip().startswith("tasks:"):
    sys.exit(0)

blocks = re.split(r"\n(?=  - )", text)
if len(blocks) < 2:
    sys.exit(0)

header = blocks[0]
task_blocks = blocks[1:]
changed = False
messages = []

issue_ref_re = re.compile(r"(?:Related:\s*#|Issue\s*#)(\d+)", re.IGNORECASE)
title_line_re = re.compile(r"^#+\s+(.+)$", re.MULTILINE)


def extract_title(order_path):
    if not order_path.is_file():
        return None
    body = order_path.read_text(encoding="utf-8")
    for line in body.splitlines():
        stripped = line.strip()
        if stripped and not stripped.startswith("#"):
            return stripped[:97] + ("..." if len(stripped) > 100 else "")
    match = title_line_re.search(body)
    if match:
        title = match.group(1).strip()
        return title[:97] + ("..." if len(title) > 100 else "")
    return None


def create_issue(order_path, title):
    for args in (
        ["gh", "issue", "create", "--title", title, "--body-file", str(order_path), "--label", "takt"],
        ["gh", "issue", "create", "--title", title, "--body-file", str(order_path)],
    ):
        result = subprocess.run(args, cwd=root, capture_output=True, text=True)
        if result.returncode == 0:
            break
    else:
        print(f"ERROR:{result.stderr.strip()}", file=sys.stdout)
        return None
    url = result.stdout.strip()
    return int(url.rstrip("/").split("/")[-1])


updated_blocks = []
for block in task_blocks:
    updated = block
    if (
        "status: pending" in block
        and "workflow: reggit-github" in block
        and not re.search(r"^\s+issue:\s+\d+\s*$", block, re.MULTILINE)
    ):
        task_dir_match = re.search(r"^\s+task_dir:\s+(.+)$", block, re.MULTILINE)
        if task_dir_match:
            order_path = root / task_dir_match.group(1).strip() / "order.md"
            if order_path.is_file():
                order_body = order_path.read_text(encoding="utf-8")
                parent_match = issue_ref_re.search(order_body)
                issue_number = None
                if parent_match:
                    issue_number = int(parent_match.group(1))
                    action = "linked"
                else:
                    title = extract_title(order_path)
                    if title:
                        issue_number = create_issue(order_path, title)
                        action = "created"
                if issue_number is not None:
                    name_match = re.search(r"^\s+name:\s+(.+)$", block, re.MULTILINE)
                    name = name_match.group(1).strip() if name_match else "unknown"
                    updated = block.rstrip() + f"\n    issue: {issue_number}\n"
                    changed = True
                    messages.append(f"{name}: {action} issue #{issue_number}")
    updated_blocks.append(updated)

if changed:
    new_text = header + "".join(
        block if block.startswith("\n") else "\n" + block for block in updated_blocks
    )
    tasks_file.write_text(new_text if new_text.endswith("\n") else new_text + "\n", encoding="utf-8")
    print("UPDATED")
    for message in messages:
        print(message)
PY
)"

if echo "$RESULT" | grep -q "^UPDATED"; then
  echo "$RESULT" | grep -v "^UPDATED" | while read -r line; do
    [[ -n "$line" ]] && log "$line"
  done
  log "pending タスクへ Issue を紐づけました"
fi
