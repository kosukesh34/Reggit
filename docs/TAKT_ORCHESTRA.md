# Takt + AI Orchestra 利用ガイド（Reggit）

Reggit では **AI Orchestra**（エージェント基盤）と **Takt**（マルチエージェントワークフロー）を併用できます。

## 前提

| ツール | 状態 | 確認コマンド |
|---|---|---|
| AI Orchestra | `score/` + `.ai-orchestra/install.json` | `ls score/RUN.md` |
| Takt | グローバルインストール | `takt --version` |
| Cursor Agent CLI | `~/.local/bin/agent` | `agent --version` |

PATH に `~/.local/bin` を追加してください:

```bash
export PATH="$HOME/.local/bin:$PATH"
```

Cursor Agent の認証（初回のみ）:

```bash
export PATH="$HOME/.local/bin:$PATH"
agent login
agent status   # ✓ Logged in と表示されれば OK
```

**コードレビューは GitHub PR で実施**します。Claude / Codex の API キーは不要です。

**重要:** Takt は git の worktree で作業するため、`.cursor/cli.json` の修正は **コミット済み** である必要があります（未コミットだと worktree に反映されず cursor-agent が失敗します）。

## CEO / PM オーケストレーション（常時稼働）

Orchestra の **CEO/PM 役** と Takt の **実装ループ** を分離しています。

```
CEO/PM (6h)  →  PM_PRIORITIES.md 更新
       ↓
Takt orchestrator (別プロセス)  →  Issue スキャン → タスクキュー + Issue 自動作成
       ↓
Takt watch (別プロセス)  →  実装 → ドラフト MR → GitHub で人間レビュー
```

**重要:** `autonomous-loop` と `takt watch` は **別 launchd サービス** です。同一プロセスだと無限ループが実装タスクをブロックします。

### 初回セットアップ

```bash
# 実装 watch + Issue 巡回オーケストレーター（両方登録）
./scripts/install-takt-daemon.sh

# CEO/PM 戦略レビュー（6 時間ごと + ログイン時 1 回）
./scripts/install-orchestra-pm-daemon.sh
```

### UI 変更と MR

Blade / CSS / LIFF を変更したタスクは、MR 本文にスクリーンショット必須:

- 保存先: `.takt/evidence/screenshots/{タスク名}/`
- ブランチにコミットし、MR 本文に Markdown 画像リンクを記載
- 詳細: `.cursor/skills/ui-screenshot-pr/SKILL.md`

- PM ログ: `.takt/daemon/pm-review.log`
- 優先度: `_documentation/PM_PRIORITIES.md`
- ルール: `.cursor/rules/ceo-pm-director.mdc`

チャットで「PM review」「優先度を見直して」と依頼しても同じスキルが動きます。

## AI Orchestra（Cursor チャット内）

Orchestra は **この Cursor チャット（エージェント）** が直接読む仕様です。

### 自動で有効になるもの

- `.cursor/rules/ai-director.mdc` — セッション開始時に learnings を読む
- `.cursor/rules/orchestra-context.mdc` — プロジェクト文脈
- `.cursor/skills/*` — コードレビュー、マイグレーション確認など 11 スキル
- `.cursor/hooks.json` — セッション終了時の learnings 更新

### よく使うコマンド（チャットで依頼）

| 依頼 | 効果 |
|---|---|
| `audit AI infra` | Orchestra 設定の健全性チェック |
| `upgrade orchestra` | `npx @neverreven/ai-orchestra init --force` 後のアップグレード適用 |
| `run the orchestra` | 再インストール / ドリフト修正 |

### 学習ドキュメント

`_documentation/AI_LEARNINGS.md` にプロジェクト固有のパターン・決定を蓄積します。セッション開始時に自動参照されます。

## Takt（ターミナル）

Takt は **ターミナル** で動かし、実装工程を **Cursor Agent** に委譲します。

### ダッシュボード（視覚的な状態確認）

```bash
./scripts/takt-dashboard.sh
```

ブラウザで `http://127.0.0.1:8765` が開きます（5秒ごと自動更新）。

- デーモン稼働状況（watch / orchestrator / pm）
- タスクキュー（running / pending / failed）
- GitHub open Issue / ドラフト PR
- 直近ログ

ログ: `.takt/daemon/dashboard.log`

### 常時稼働（トリガー不要）

```bash
# 初回のみ: macOS ログイン時に自動起動 + クラッシュ時再起動
./scripts/install-takt-daemon.sh
```

- `takt watch` が常駐し、`.takt/tasks.yaml` の pending タスクを自動実行
- `autonomous-loop` タスク（`auto-improvement-loop`）が GitHub Issue/PR を巡回し、改善タスクを自動キュー
- キューされた実装タスクには **GitHub Issue を自動作成**（`enqueue_from_issue` / `enqueue_fresh` で `issue.create: true`）
- 起動時に `scripts/takt-ensure-task-issues.sh` が Issue 未紐づけの pending タスクを補完
- ログ: `.takt/daemon/stdout.log`

Reggit アプリ本体は `docker compose` の `restart: unless-stopped` で常時稼働（ポート 8080）。

### 基本フロー

```bash
cd /Users/kosuke/Reggit
export PATH="$HOME/.local/bin:$PATH"

# 手動でウォッチャー起動（デーモン未使用時）
./scripts/takt-watch.sh

# 別ターミナルでタスク追加
takt add "#56"          # GitHub Issue から（対話で workflow 選択）
# または .takt/tasks.yaml に pending タスクを追加

# 手動で一括実行
takt run

# 結果の確認・マージ
takt list
```

`takt` の対話中に「Queue as task」を選ぶと `.takt/tasks/` に保存されます。

### Reggit 向け設定

`.takt/config.yaml` で以下を設定済みです:

- `language: ja`
- `auto_pr: true` / `draft_pr: true` — 完了後にドラフト PR を自動作成
- `coder` / `planner` → `cursor` プロバイダ（`composer-2-fast`）
- 推奨ワークフロー: **`reggit-github`**（計画 → 実装 → PR。レビューは GitHub）

### ワークフロー選択

`takt` 起動時にワークフローを選べます:

| ワークフロー | 用途 |
|---|---|
| **`reggit-github`** | 計画 → 実装 → ドラフト PR（**推奨**。レビューは GitHub） |
| `backend` | 内部 AI レビューループ付き（API キーが別途必要な場合あり） |
| `backend-mini` | 軽量版（内部レビューあり） |

### GitHub でのレビューフロー

```bash
takt run                    # 実装完了 → ドラフト PR 自動作成
# GitHub でレビュー（人間 / CodeRabbit 等）
takt --pr 123               # レビューコメントを取り込んで修正（任意）
```

### GitHub Issue からタスク化

```bash
takt add #123
takt run
```

## ファイル構成

```
Reggit/
├── score/                    # AI Orchestra 仕様（init でコピー）
├── .ai-orchestra/
│   └── install.json          # インストール記録
├── .cursor/
│   ├── rules/ai-director.mdc
│   ├── rules/orchestra-context.mdc
│   ├── skills/               # Orchestra + 既存 skills
│   ├── hooks.json
│   └── mcp.json              # MCP スロット（プレースホルダ）
├── .takt/
│   ├── config.yaml           # Takt プロジェクト設定
│   ├── tasks/                # キューされたタスク（gitignore）
│   └── runs/                 # 実行ログ（gitignore）
├── AGENTS.md
└── _documentation/AI_LEARNINGS.md
```

## アップグレード

```bash
# Orchestra 仕様の更新
npx @neverreven/ai-orchestra@latest init --force
# → Cursor チャットで「upgrade orchestra」

# Takt の更新
npm install -g takt@latest
```

## 参考リンク

- [Takt](https://github.com/nrslib/takt)
- [AI Orchestra](https://github.com/neverreven/ai-orchestra)
- [Takt で Codex・Cursor・Claude Code を協調](https://zenn.dev/coji/articles/takt-multi-agent-coding-experience)
