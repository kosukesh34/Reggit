#!/usr/bin/env bash
# GitHub ブランチ保護ルールを設定するスクリプト
# - マージ前に CI（テスト・Lint）の成功を必須にする
# - マージ前に PR レビュー 1 件以上を必須にする
#
# ※ Copilot のコードレビューについて
#   GitHub Copilot のレビューは「コメント」のみで Approve にならないため、
#   「Copilot の承認がなければマージできない」という設定はできません。
#   Copilot を自動でレビュアーに追加するだけなら、GitHub の
#   リポジトリ設定 → Rules → Rulesets で「Pull request に Copilot を追加」を有効にできます。
#
# 前提: GitHub CLI (gh) がインストール済みで、gh auth login 済みであること
# 注意: 初回は main/develop で一度 CI が通ってから実行してください（ステータスチェック名が登録されます）

set -e

REPO="${GITHUB_REPO:-$(gh repo view --json nameWithOwner -q .nameWithOwner)}"
BRANCHES="${PROTECTED_BRANCHES:-main develop}"

# CI ワークフロー (.github/workflows/ci.yml) のジョブ名に対応するステータスチェック
# test・lint・frontend の結果をまとめる集約ジョブ main だけを必須にする
REQUIRED_CONTEXTS='["main"]'

echo "Repository: $REPO"
echo "Protected branches: $BRANCHES"
echo "Required status checks: $REQUIRED_CONTEXTS"
echo ""

for BRANCH in $BRANCHES; do
  echo "Setting protection for branch: $BRANCH"
  # ブランチ保護ルールを API で設定（gh には専用コマンドがないため REST API を使用）
  # https://docs.github.com/rest/branches/branch-protection#update-branch-protection
  jq -n \
    --argjson contexts "$REQUIRED_CONTEXTS" \
    '{
      required_status_checks: {
        strict: false,
        contexts: $contexts
      },
      enforce_admins: false,
      required_pull_request_reviews: {
        dismissal_restrictions: null,
        dismiss_stale_reviews: true,
        require_code_owner_reviews: false,
        required_approving_review_count: 1,
        require_last_push_approval: false
      },
      restrictions: null,
      allow_force_pushes: false,
      allow_deletions: false
    }' | gh api -X PUT "repos/$REPO/branches/$BRANCH/protection" --input -
  echo "  -> Done."
done

echo ""
echo "Branch protection has been applied. Merging is now allowed only when:"
echo "  - Required status checks (CI main) have passed"
echo "  - At least 1 pull request review approval is present"
