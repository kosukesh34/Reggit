# Reggit — Agent Context

Laravel ベースのマルチテナント会員・予約・ポイント管理 SaaS。クリーンアーキテクチャ（UseCase 層）を採用。

## CEO / PM オーケストレーション

| 役割 | 何をする | 常時稼働 |
|---|---|---|
| **CEO/PM** | 優先度・スコープ（`PM_PRIORITIES.md`） | `install-orchestra-pm-daemon.sh`（6h） |
| **Takt supervisor** | 次の 1 件を選びキュー | `autonomous-loop` |
| **Takt coder** | 実装 → ドラフト PR | `takt watch` |

戦略: `_documentation/PM_PRIORITIES.md` — ルール: `.cursor/rules/ceo-pm-director.mdc`

## 主要ドキュメント

| ドキュメント | 用途 |
|---|---|
| `docs/CLEAN_ARCHITECTURE.md` | アーキテクチャ規約 |
| `CODING_STANDARDS.md` | コーディング規約 |
| `docs/CODE_RULES_COMPLIANCE.md` | 規約遵守状況 |
| `_documentation/AI_LEARNINGS.md` | セッション横断の学習・決定ログ |
| `_documentation/PM_PRIORITIES.md` | CEO/PM 優先度バックログ |

## 開発コマンド

```bash
docker compose exec app php artisan test tests/Feature/SomeTest.php
```

<!-- ai-orchestra: managed-section start -->
# Project context — managed by ai-orchestra

> Generated content. Do not edit between the markers — your edits will be overwritten on the next orchestra run. Add your own content **above** or **below** the marker pair instead.

## Identity

- **Project:** Reggit
- **Stacks detected:** php (0.90), laravel (0.95)
- **Frameworks:** laravel, php, blade, docker
- **Orchestra version:** 3.2.2
- **Cursor adapter version:** 1.0.0-alpha
- **Installed:** 2026-06-10T00:00:00.000Z

## Roles installed

- **backend-engineer** — APIs, business logic, persistence, integrations
- **devops-sre** — CI/CD, deployment, observability
- **qa-engineer** — test strategy, coverage, pre-release sign-off
- **security-engineer** — auth, secrets, CVE response

## Skills installed

- **audit:** ai-infra-audit, cleanup, pre-release
- **code:** api-design-review, code-review, db-migration-review, dependency-audit
- **docs:** write-test-plan
- **quality:** auth-flow-review, secrets-scan, security-baseline

## How to invoke the orchestra

- **Start a session** — Cursor reads `ai-director.mdc` and `orchestra-context.mdc` automatically.
- **Audit the orchestra** — say "audit AI infra".
- **Update orchestra core** — `npx @neverreven/ai-orchestra init --force` then "upgrade orchestra".

## Pointers

- Director rule: `.cursor/rules/ai-director.mdc`
- Always-on context: `.cursor/rules/orchestra-context.mdc`
- Skills: `.cursor/skills/<skill>/SKILL.md`
- Learnings: `_documentation/AI_LEARNINGS.md`
- Install marker: `.ai-orchestra/install.json`
<!-- ai-orchestra: managed-section end -->
