# GitHub Pull Request 作成ワークフロー（Reggit）

本ドキュメントは、Reggit リポジトリで **Pull Request（PR）** を安全に作成・レビューするための実務手順です。  
詳細なレビュー観点は [MR_AND_CODE_REVIEW_PROCEDURE.md](./MR_AND_CODE_REVIEW_PROCEDURE.md) を参照してください。

---

## 1. 前提

| 項目 | Reggit の運用 |
|------|----------------|
| ホスティング | GitHub（`kosukesh34/Reggit`） |
| マージ先 | 原則 `main` |
| CI | `.github/workflows/ci.yml`（PR 向けにテスト・Lint） |
| CLI | `gh`（GitHub CLI）を推奨。未導入の場合は Web UI でも可 |
| テンプレート | `.github/PULL_REQUEST_TEMPLATE.md` および `docs/pull_request_templates/` |

---

## 2. 作業の流れ（概要）

```mermaid
flowchart LR
  A[ブランチ作成] --> B[実装・テスト]
  B --> C[コミット]
  C --> D[push]
  D --> E[PR 作成]
  E --> F[CI 確認]
  F --> G[レビュー・マージ]
```

---

## 3. ブランチとコミット

### 3.1 ブランチ名

次のいずれかのプレフィックスを使う。

| プレフィックス | 用途 | 例 |
|----------------|------|-----|
| `feature/` | 新機能・仕様追加 | `feature/wallet-pass-prefetch` |
| `fix/` | バグ修正 | `fix/liff-member-identity` |
| `chore/` | ツール・ドキュメント・CI | `chore/pr-workflow-doc` |
| `refactor/` | 挙動を変えない整理 | `refactor/member-use-case` |

### 3.2 コミットメッセージ

1 行目は英語または日本語で **何を・なぜ** を短く書く。必要なら本文に補足する。

```
fix: LIFF 起動時に既存会員が未登録扱いになる

- UserService で LINE ID 付き既存ユーザーをセッションより優先
- 会員タブは LINE ID 確定後に members/me を呼ぶ
```

### 3.3 コミットに含めないもの

- `.env`、秘密鍵、`.p8`、個人の ngrok URL
- `.phpunit.result.cache` などのローカル生成物
- 意図しない大規模フォーマット差分

---

## 4. PR 作成前チェック

PR を出す前に、次を確認する。**順序: アーキテクチャ → デグレ → テスト・セキュリティ**（詳細は [MR_AND_CODE_REVIEW_PROCEDURE.md](./MR_AND_CODE_REVIEW_PROCEDURE.md)）。

### アーキテクチャ

- [ ] Controller にビジネスロジックが入っていない（UseCase に委譲）
- [ ] UseCase から UseCase を呼んでいない
- [ ] `new` で Service/Model のビジネス依存を生成していない
- [ ] 同種の既存コード（system-admin 等）とパターンが一致している

### デグレ

- [ ] ソート・一覧順・既存分岐の削除が意図的か（意図的なら PR 本文に記載）

### テスト・その他

- [ ] `origin/main`（または指定のベースブランチ）を取り込み、コンフリクトがない
- [ ] 変更範囲に対応するテストを追加・更新した（不要と判断した場合は PR 本文に理由を書く）
- [ ] Docker 環境で `php artisan test` が通る（例: `docker compose exec app php artisan test tests/Unit/XXXTest.php`）
- [ ] マルチテナント（`project_id`）のスコープ漏れがない
- [ ] 機密情報をコミットしていない

**PHP 変更がある場合**は、少なくとも変更したテストクラスを実行する。フロントのみの変更は、LIFF・管理画面など該当画面を実機またはブラウザで確認する。

---

## 5. PR の作成（GitHub CLI）

### 5.1 リモートへ push

```bash
git push -u origin HEAD
```

### 5.2 PR 作成

```bash
gh pr create \
  --base main \
  --title "fix: タイトル" \
  --body-file docs/pull_request_templates/bugfix.md
```

下書きにする場合:

```bash
gh pr create --draft --base main --title "WIP: ..." --body "..."
```

### 5.3 作成後

1. PR ページで **Checks**（CI）が成功するまで待つ
2. レビュアーをアサイン（チーム運用に合わせる）
3. 自己レビュー用に **Files changed** を上から確認する

---

## 6. PR 本文の書き方

共通テンプレート: [.github/PULL_REQUEST_TEMPLATE.md](../.github/PULL_REQUEST_TEMPLATE.md)

種別ごとのたたき台:

| 種別 | ファイル |
|------|----------|
| バグ修正 | [docs/pull_request_templates/bugfix.md](./pull_request_templates/bugfix.md) |
| 機能追加 | [docs/pull_request_templates/feature.md](./pull_request_templates/feature.md) |
| 改善・リファクタ | [docs/pull_request_templates/improvement.md](./pull_request_templates/improvement.md) |

**必ず書くこと**

- 何が問題で、何をどう直したか（レビュアーが diff だけで追わなくてよい粒度）
- 影響範囲（API・LIFF・管理画面・マイグレーションなど）
- 確認手順（再現 → 修正後の期待結果）

---

## 7. レビューとマージ

1. CI が緑であること
2. [MR_AND_CODE_REVIEW_PROCEDURE.md](./MR_AND_CODE_REVIEW_PROCEDURE.md) の必須項目を満たすこと
3. 指摘対応後、Approve を得てマージ
4. マージ後、不要なブランチは削除してよい（`gh pr merge` 時に `--delete-branch` 可）

---

## 8. よくあるトラブル

| 現象 | 対処 |
|------|------|
| `gh: not logged in` | `gh auth login` |
| CI の PHP バージョン差 | ローカルは Docker の `app` コンテナでテスト |
| PR が空に見える | ベースブランチと compare ブランチの向きを確認 |
| 巨大な diff | `.phpunit.result.cache` などをコミットしていないか確認 |

---

## 9. 関連ドキュメント

- [MR_AND_CODE_REVIEW_PROCEDURE.md](./MR_AND_CODE_REVIEW_PROCEDURE.md) — レビュー観点・コーディング規則
- [CODE_RULES_COMPLIANCE.md](./CODE_RULES_COMPLIANCE.md) — アーキテクチャ・規約
- [CLEAN_ARCHITECTURE.md](./CLEAN_ARCHITECTURE.md) — UseCase / Controller の役割
