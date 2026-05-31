---
name: create-pull-request
description: Reggit で変更をコミットし GitHub Pull Request を作成する。PR作成・gh pr・プルリク・MR作成と依頼されたときに使う。
---

# Reggit: Pull Request 作成スキル

GitHub（`gh`）向け。手順の詳細は [docs/GITHUB_PULL_REQUEST_WORKFLOW.md](../../docs/GITHUB_PULL_REQUEST_WORKFLOW.md) を参照。

## 1. 認証確認

```bash
gh auth status
```

未ログインなら `gh auth login` を案内して終了する。

## 2. 未コミット変更の確認

```bash
git status -sb
git diff --stat
```

未コミットがある場合:

- ユーザーに一覧を示す
- 「このままコミットして PR を作成してよいか」確認する
- `.phpunit.result.cache` や `.env` はステージしない

## 3. ベースブランチの確定

- デフォルト: `main`
- ユーザー指定があればそれに従う

```bash
git fetch origin
git merge-base HEAD origin/main
```

## 4. PR 前の軽い確認

変更ファイル一覧:

```bash
git diff <merge-base> HEAD --name-only
```

- PHP 変更: 関連テストを `docker compose exec app php artisan test <path>` で実行できるなら実行
- フロント変更: LIFF / 管理画面の確認手順を PR 本文に書く
- CRITICAL 相当（`project_id` 漏れ、秘密鍵コミット）があれば修正を促す

## 5. ブランチ・コミット・push

ブランチ名は `fix/` `feature/` `chore/` など用途に合わせる。

```bash
git add <対象ファイル>
git commit -m "$(cat <<'EOF'
fix: 短い要約

EOF
)"
git push -u origin HEAD
```

## 6. PR 作成

種別に応じた body テンプレート:

| 種別 | ファイル |
|------|----------|
| バグ | `docs/pull_request_templates/bugfix.md` |
| 機能 | `docs/pull_request_templates/feature.md` |
| 改善 | `docs/pull_request_templates/improvement.md` |

```bash
gh pr create --base main --title "<プレフィックス>: <タイトル>" --body "$(cat <<'EOF'
（テンプレートに沿って記入）

EOF
)"
```

下書きの場合は `--draft` を付ける。

## 7. 完了報告

- PR URL をユーザーに返す
- CI（Checks）の確認を促す
- レビュー観点は [docs/MR_AND_CODE_REVIEW_PROCEDURE.md](../../docs/MR_AND_CODE_REVIEW_PROCEDURE.md) へ誘導

## Reggit で避けること

- `git config` の変更
- `--no-verify` や force push（ユーザー明示時以外）
- 秘密鍵・`.env` のコミット
