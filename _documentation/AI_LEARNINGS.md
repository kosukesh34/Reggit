# Reggit — AI Learnings

> Living context inherited by every AI session in this project. Maintained by the agent under the Director Rule. Read at session start; updated when learnings emerge during a session.

| Property | Value |
|----------|-------|
| Created | 2026-06-10 |
| Last updated | 2026-06-10 |
| Maintained by | AI agent (Director Rule), with human oversight |
| Size budget | ~300 lines (consolidate beyond this) |

---

## How this document works

- **Read at session start** — every fresh conversation begins by reading this file (per the Director Rule).
- **Updated during sessions** — when a learning emerges (user correction, gotcha, decision), it is written here immediately.
- **Reviewed at session end** — if a stop-hook is wired, it sweeps for anything missed.
- **Audited periodically** — the `ai-infra-audit` skill verifies structure and budget.

What belongs here, and what does NOT belong here, is described in the Director Rule. Read it before adding entries.

---

## Established patterns

### Clean Architecture（UseCase 連鎖なし）

- **Context:** Laravel アプリケーション層全般。
- **Pattern:** Controller → UseCase → Service/Model。UseCase 同士の連鎖はしない。
- **Why:** `docs/CLEAN_ARCHITECTURE.md` で定義された Reggit 固有のアーキテクチャ。
- **Reference:** `docs/CLEAN_ARCHITECTURE.md`

### マルチテナントの project_id スコープ

- **Context:** データアクセス・API・管理画面。
- **Pattern:** クエリは必ず `project_id` でスコープする。
- **Why:** テナント間のデータ漏洩防止。
- **Reference:** `CODING_STANDARDS.md`

### PHP テストは Docker 経由

- **Context:** PHPUnit / Feature テスト実行時。
- **Pattern:** `docker compose exec app php artisan test <ファイル>`
- **Why:** 本番に近い PHP 拡張・DB 環境で実行するため。
- **Reference:** `.cursor/rules/github-pull-request.mdc`

---

## Anti-patterns

_No entries yet._

---

## User preferences

- **Preference:** ユーザーが明示的に依頼しない限り git commit しない。
- **Stated on:** 2026-06-10.
- **Scope:** 常時（ユーザールール）。

- **Preference:** 応答は日本語で行う。
- **Stated on:** 2026-06-10.
- **Scope:** 常時。

- **Preference:** Takt のコードレビューは GitHub PR で実施する（Claude/Codex API キーは不要）。
- **Stated on:** 2026-06-10.
- **Scope:** Takt ワークフロー（`reggit-github` + `auto_pr`）。

- **Preference:** Takt タスクのブランチは常に `main` から切る。ブランチ名・Issue タイトルは英語 kebab-case（ローマ字不可）。CI 失敗 PR は修正タスクを最優先。未コミット変更を残さない。
- **Stated on:** 2026-06-10.
- **Scope:** Takt / Orchestra 自律ループ。

- **Preference:** Reggit / `~/takt-worktrees` 内の操作は承認不要。他プロジェクトのパス・別 git リポジトリ操作のみ承認を求める（`.cursor/permissions.json` + `before-shell-guard.sh`）。
- **Stated on:** 2026-06-10.
- **Scope:** Cursor Agent / Takt（cursor-agent）。

---

## Decision log

### 2026-06-10 — AI Orchestra + Takt 導入

- **Context:** 複数 AI エージェントの協調とプロジェクト向けエージェント基盤が必要。
- **Decision:** ai-orchestra（Tier 1 Score）を Cursor にインストールし、Takt で実装担当を Cursor Agent に割り当てる。
- **Alternatives considered:** Orchestra のみ / Takt のみ。
- **Consequences:** `.cursor/rules/ai-director.mdc`、skills、Takt 設定が追加される。
- **Status:** accepted

---

## Environment notes

- **Quirk:** `cursor-agent` CLI は `~/.local/bin/agent` にインストールされる。
- **Symptoms:** `cursor-agent` / `agent` が PATH に無いと Takt の Cursor プロバイダが失敗する。
- **Workaround / fix:** `export PATH="$HOME/.local/bin:$PATH"` をシェルプロファイルに追加。

---

## Consolidation log

_No consolidations yet._
