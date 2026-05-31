#!/usr/bin/env bash
# GitHub リポジトリ設定（PR マージ後のブランチ自動削除など）
#
# 前提: GitHub CLI (gh) がインストール済みで、gh auth login 済みであること

set -e

REPO="${GITHUB_REPO:-$(gh repo view --json nameWithOwner -q .nameWithOwner)}"

echo "Repository: $REPO"
echo "Enabling delete_branch_on_merge..."
gh api -X PATCH "repos/$REPO" -f delete_branch_on_merge=true >/dev/null

echo "Done. Merged pull request branches will be deleted automatically."
