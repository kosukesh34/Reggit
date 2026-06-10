#!/usr/bin/env python3
"""Reggit Takt 自律実行のローカルステータスダッシュボード."""
from __future__ import annotations

import json
import os
import re
import subprocess
import sys
from datetime import datetime, timezone
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from typing import Any
from urllib.parse import urlparse

try:
    import yaml
except ImportError:
    yaml = None  # type: ignore

ROOT = Path(__file__).resolve().parent.parent
DAEMON_DIR = ROOT / ".takt" / "daemon"
TASKS_FILE = ROOT / ".takt" / "tasks.yaml"
DEFAULT_PORT = 8765

DAEMONS = [
    ("com.reggit.takt-watch", "Takt Watch（実装）", "stdout.log"),
    ("com.reggit.takt-orchestrator", "Orchestrator（Issue 巡回）", "orchestrator.log"),
    ("com.reggit.orchestra-pm", "CEO/PM レビュー", "pm-review.log"),
]


def run_cmd(cmd: list[str], timeout: int = 8) -> tuple[int, str]:
    env = os.environ.copy()
    env["PATH"] = os.pathsep.join(
        [
            str(Path.home() / ".local" / "bin"),
            "/opt/homebrew/bin",
            "/usr/local/bin",
            env.get("PATH", ""),
        ]
    )
    try:
        result = subprocess.run(
            cmd,
            cwd=ROOT,
            capture_output=True,
            text=True,
            timeout=timeout,
            env=env,
        )
        out = (result.stdout or "") + (result.stderr or "")
        return result.returncode, out.strip()
    except (subprocess.TimeoutExpired, FileNotFoundError) as exc:
        return 1, str(exc)


def tail_file(path: Path, lines: int = 25) -> list[str]:
    if not path.is_file():
        return []
    try:
        content = path.read_text(encoding="utf-8", errors="replace").splitlines()
        return content[-lines:]
    except OSError:
        return []


def launchctl_status(label: str) -> dict[str, Any]:
    code, out = run_cmd(["launchctl", "list"])
    if code != 0:
        return {"label": label, "registered": False, "running": False, "pid": None, "exit_code": None}
    for line in out.splitlines():
        parts = line.split()
        if len(parts) >= 3 and parts[2] == label:
            pid = int(parts[0]) if parts[0].isdigit() else None
            exit_code = int(parts[1]) if parts[1].lstrip("-").isdigit() else None
            running = pid is not None and pid > 0
            return {
                "label": label,
                "registered": True,
                "running": running,
                "pid": pid,
                "exit_code": exit_code,
            }
    return {"label": label, "registered": False, "running": False, "pid": None, "exit_code": None}


def json_safe(value: Any) -> Any:
    if isinstance(value, datetime):
        return value.isoformat()
    if isinstance(value, dict):
        return {k: json_safe(v) for k, v in value.items()}
    if isinstance(value, list):
        return [json_safe(v) for v in value]
    return value


def parse_tasks() -> list[dict[str, Any]]:
    if not TASKS_FILE.is_file():
        return []
    text = TASKS_FILE.read_text(encoding="utf-8")
    if yaml is None:
        return []
    data = yaml.safe_load(text) or {}
    tasks = data.get("tasks") or []
    result = []
    for task in tasks:
        if not isinstance(task, dict):
            continue
        issue = task.get("issue")
        result.append(
            json_safe(
                {
                    "name": task.get("name", ""),
                    "status": task.get("status", "unknown"),
                    "workflow": task.get("workflow", ""),
                    "issue": issue,
                    "issue_url": f"https://github.com/kosukesh34/Reggit/issues/{issue}" if issue else None,
                    "pr_url": task.get("pr_url"),
                    "summary": (task.get("summary") or "")[:120],
                    "started_at": task.get("started_at"),
                    "failure": (task.get("failure") or {}).get("error"),
                }
            )
        )
    order = {"running": 0, "pending": 1, "failed": 2, "completed": 3}
    result.sort(key=lambda t: (order.get(t["status"], 9), t["name"]))
    return result


def fetch_github() -> dict[str, Any]:
    code, out = run_cmd(
        [
            "gh",
            "pr",
            "list",
            "--state",
            "open",
            "--head",
            "takt/",
            "--json",
            "number,title,url,isDraft",
            "--limit",
            "10",
        ]
    )
    prs = json.loads(out) if code == 0 and out.startswith("[") else []

    code2, out2 = run_cmd(
        [
            "gh",
            "issue",
            "list",
            "--state",
            "open",
            "--json",
            "number,title,url",
            "--limit",
            "10",
        ]
    )
    issues = json.loads(out2) if code2 == 0 and out2.startswith("[") else []

    return {"prs": prs, "issues": issues, "gh_available": code == 0 or code2 == 0}


def agent_status() -> dict[str, Any]:
    code, out = run_cmd(["agent", "status"], timeout=5)
    logged_in = "Logged in" in out
    email_match = re.search(r"Logged in as (.+)", out)
    return {
        "logged_in": logged_in,
        "email": email_match.group(1).strip() if email_match else None,
        "raw": out[:200] if out else None,
        "ok": code == 0 and logged_in,
    }


def build_status() -> dict[str, Any]:
    daemons = []
    for label, title, log_name in DAEMONS:
        st = launchctl_status(label)
        st["title"] = title
        st["log_file"] = str(DAEMON_DIR / log_name)
        st["log_tail"] = tail_file(DAEMON_DIR / log_name, 15)
        daemons.append(st)

    tasks = parse_tasks()
    counts = {
        "running": sum(1 for t in tasks if t["status"] == "running"),
        "pending": sum(1 for t in tasks if t["status"] == "pending"),
        "failed": sum(1 for t in tasks if t["status"] == "failed"),
        "completed": sum(1 for t in tasks if t["status"] == "completed"),
    }

    return {
        "updated_at": datetime.now(timezone.utc).astimezone().isoformat(timespec="seconds"),
        "project_root": str(ROOT),
        "agent": agent_status(),
        "daemons": daemons,
        "tasks": tasks,
        "task_counts": counts,
        "github": fetch_github(),
        "logs": {
            "watch": tail_file(DAEMON_DIR / "stdout.log", 20),
            "orchestrator": tail_file(DAEMON_DIR / "orchestrator.log", 15),
        },
    }


HTML_PAGE = """<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reggit Takt Dashboard</title>
  <style>
    :root {
      --bg: #0f1419;
      --card: #1a2332;
      --border: #2d3a4f;
      --text: #e7ecf3;
      --muted: #8b9cb3;
      --green: #3dd68c;
      --yellow: #f5c542;
      --red: #f87171;
      --blue: #60a5fa;
      --purple: #a78bfa;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "SF Pro Text", "Hiragino Sans", system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.5;
    }
    header {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
      justify-content: space-between;
      background: linear-gradient(180deg, #151d28 0%, var(--bg) 100%);
    }
    h1 { margin: 0; font-size: 1.25rem; font-weight: 600; }
    .meta { color: var(--muted); font-size: 0.85rem; }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1rem;
      padding: 1rem 1.5rem;
    }
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1rem 1.1rem;
    }
    .card h2 {
      margin: 0 0 0.75rem;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--muted);
    }
    .stat-row { display: flex; gap: 0.75rem; flex-wrap: wrap; }
    .stat {
      flex: 1;
      min-width: 70px;
      padding: 0.6rem 0.75rem;
      border-radius: 8px;
      background: #111827;
      text-align: center;
    }
    .stat .n { font-size: 1.5rem; font-weight: 700; }
    .stat .l { font-size: 0.75rem; color: var(--muted); }
    .badge {
      display: inline-block;
      padding: 0.15rem 0.5rem;
      border-radius: 999px;
      font-size: 0.72rem;
      font-weight: 600;
    }
    .badge.running { background: #14532d; color: var(--green); }
    .badge.pending { background: #422006; color: var(--yellow); }
    .badge.failed { background: #450a0a; color: var(--red); }
    .badge.completed { background: #1e3a5f; color: var(--blue); }
    .badge.stopped { background: #374151; color: var(--muted); }
    .badge.ok { background: #14532d; color: var(--green); }
    .badge.warn { background: #422006; color: var(--yellow); }
    table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    th, td { text-align: left; padding: 0.45rem 0.35rem; border-bottom: 1px solid var(--border); }
    th { color: var(--muted); font-weight: 500; font-size: 0.75rem; }
    a { color: var(--blue); text-decoration: none; }
    a:hover { text-decoration: underline; }
    .daemon { margin-bottom: 0.75rem; }
    .daemon-title { font-weight: 600; font-size: 0.9rem; }
    .daemon-meta { font-size: 0.8rem; color: var(--muted); }
    pre.log {
      margin: 0;
      padding: 0.75rem;
      background: #0b1020;
      border-radius: 8px;
      font-size: 0.72rem;
      line-height: 1.45;
      overflow-x: auto;
      max-height: 220px;
      overflow-y: auto;
      white-space: pre-wrap;
      word-break: break-all;
      color: #c9d1d9;
    }
    .full { grid-column: 1 / -1; }
    .error { color: var(--red); font-size: 0.8rem; }
    @media (max-width: 640px) {
      .grid { padding: 0.75rem; }
    }
  </style>
</head>
<body>
  <header>
    <div>
      <h1>Reggit Takt Dashboard</h1>
      <div class="meta" id="updated">読み込み中…</div>
    </div>
    <div id="agent-badge"></div>
  </header>
  <div class="grid">
    <div class="card">
      <h2>タスクキュー</h2>
      <div class="stat-row" id="stats"></div>
    </div>
    <div class="card">
      <h2>デーモン</h2>
      <div id="daemons"></div>
    </div>
    <div class="card full">
      <h2>タスク一覧</h2>
      <table>
        <thead>
          <tr><th>状態</th><th>タスク</th><th>Issue</th><th>PR</th><th>ワークフロー</th></tr>
        </thead>
        <tbody id="tasks"></tbody>
      </table>
    </div>
    <div class="card">
      <h2>GitHub（open）</h2>
      <div id="github"></div>
    </div>
    <div class="card full">
      <h2>実装ログ（watch）</h2>
      <pre class="log" id="log-watch"></pre>
    </div>
    <div class="card full">
      <h2>巡回ログ（orchestrator）</h2>
      <pre class="log" id="log-orch"></pre>
    </div>
  </div>
  <script>
    function badge(status, text) {
      return `<span class="badge ${status}">${text}</span>`;
    }
    function esc(s) {
      return String(s ?? "").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
    }
    async function refresh() {
      try {
        const res = await fetch("/api/status");
        const d = await res.json();
        document.getElementById("updated").textContent =
          `最終更新: ${d.updated_at}（5秒ごと自動更新）`;
        const agent = d.agent;
        document.getElementById("agent-badge").innerHTML = agent.ok
          ? badge("ok", `cursor-agent: ${agent.email || "ログイン済"}`)
          : badge("warn", "cursor-agent: 未ログイン");
        const c = d.task_counts;
        document.getElementById("stats").innerHTML = [
          ["running", c.running, "実行中"],
          ["pending", c.pending, "待機"],
          ["failed", c.failed, "失敗"],
          ["completed", c.completed, "完了"],
        ].map(([cls, n, label]) =>
          `<div class="stat"><div class="n">${n}</div><div class="l">${label}</div></div>`
        ).join("");
        document.getElementById("daemons").innerHTML = d.daemons.map(dm => `
          <div class="daemon">
            <div class="daemon-title">${esc(dm.title)} ${dm.running ? badge("running","稼働") : badge("stopped","停止")}</div>
            <div class="daemon-meta">${esc(dm.label)}${dm.pid ? ` · PID ${dm.pid}` : ""}</div>
          </div>`).join("");
        document.getElementById("tasks").innerHTML = d.tasks.map(t => `
          <tr>
            <td>${badge(t.status, t.status)}</td>
            <td><strong>${esc(t.name)}</strong>${t.failure ? `<div class="error">${esc(t.failure).slice(0,80)}</div>` : ""}</td>
            <td>${t.issue_url ? `<a href="${t.issue_url}" target="_blank">#${t.issue}</a>` : "—"}</td>
            <td>${t.pr_url ? `<a href="${t.pr_url}" target="_blank">PR</a>` : "—"}</td>
            <td>${esc(t.workflow)}</td>
          </tr>`).join("") || "<tr><td colspan='5'>タスクなし</td></tr>";
        const gh = d.github;
        let ghHtml = "";
        if (!gh.gh_available) {
          ghHtml = "<p class='error'>gh CLI 未利用</p>";
        } else {
          ghHtml += "<p><strong>ドラフト PR</strong></p><ul>" +
            (gh.prs.length ? gh.prs.map(p =>
              `<li><a href="${p.url}" target="_blank">#${p.number}</a> ${esc(p.title)}${p.isDraft ? " (draft)" : ""}</li>`
            ).join("") : "<li>なし</li>") + "</ul>";
          ghHtml += "<p><strong>Open Issues</strong></p><ul>" +
            (gh.issues.slice(0,8).map(i =>
              `<li><a href="${i.url}" target="_blank">#${i.number}</a> ${esc(i.title)}</li>`
            ).join("") || "<li>なし</li>") + "</ul>";
        }
        document.getElementById("github").innerHTML = ghHtml;
        document.getElementById("log-watch").textContent =
          (d.logs.watch || []).join("\\n") || "(ログなし)";
        document.getElementById("log-orch").textContent =
          (d.logs.orchestrator || []).join("\\n") || "(ログなし)";
      } catch (e) {
        document.getElementById("updated").textContent = "取得エラー: " + e;
      }
    }
    refresh();
    setInterval(refresh, 5000);
  </script>
</body>
</html>
"""


class DashboardHandler(BaseHTTPRequestHandler):
    def log_message(self, format: str, *args: Any) -> None:
        return  # quiet

    def do_GET(self) -> None:
        path = urlparse(self.path).path
        if path == "/api/status":
            body = json.dumps(build_status(), ensure_ascii=False).encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "application/json; charset=utf-8")
            self.send_header("Cache-Control", "no-store")
            self.end_headers()
            self.wfile.write(body)
            return
        if path in ("/", "/index.html"):
            body = HTML_PAGE.encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "text/html; charset=utf-8")
            self.end_headers()
            self.wfile.write(body)
            return
        self.send_response(404)
        self.end_headers()


def main() -> None:
    port = int(os.environ.get("TAKT_DASHBOARD_PORT", DEFAULT_PORT))
    server = ThreadingHTTPServer(("127.0.0.1", port), DashboardHandler)
    print(f"Takt Dashboard: http://127.0.0.1:{port}/", flush=True)
    print("Ctrl+C で停止", flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n停止しました", flush=True)
        server.shutdown()


if __name__ == "__main__":
    main()
