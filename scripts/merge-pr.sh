#!/usr/bin/env bash
set -euo pipefail
pr=${1:?Usage: scripts/merge-pr.sh PR_NUMBER}
[[ "$pr" =~ ^[0-9]+$ ]] || { echo 'PR number must be numeric' >&2; exit 1; }
cd "$(git rev-parse --show-toplevel)"
[[ -z "$(git status --porcelain)" ]] || { echo 'Merge requires a clean checkout' >&2; exit 1; }
snapshot=$(gh pr view "$pr" --json headRefOid,state,baseRefName)
head_sha=$(python3 -c 'import json,sys
p=json.load(sys.stdin)
if p["state"] != "OPEN" or p["baseRefName"] != "main":
    sys.exit("PR must be open and target main")
print(p["headRefOid"])' <<< "$snapshot")
[[ "$(git rev-parse HEAD)" == "$head_sha" ]] || { echo 'Checkout must match the latest PR HEAD' >&2; exit 1; }
git fetch origin main
git merge-base --is-ancestor origin/main HEAD || { echo 'Merge latest origin/main into this PR first' >&2; exit 1; }
# Wait for GitHub CI, then require the aggregate "main" check to have passed on this exact HEAD.
gh pr checks "$pr" --watch --interval 30 >/dev/null || true
conclusion=$(gh api "repos/{owner}/{repo}/commits/$head_sha/check-runs?check_name=main" --jq '.check_runs[0].conclusion // ""')
[[ "$conclusion" == success ]] || { echo "GitHub CI main check must succeed on $head_sha (got: ${conclusion:-none})" >&2; exit 1; }
[[ "$(git rev-parse HEAD)" == "$head_sha" && -z "$(git status --porcelain)" ]] || { echo 'Checkout changed after validation' >&2; exit 1; }
[[ "$(gh pr view "$pr" --json headRefOid --jq .headRefOid)" == "$head_sha" ]] || { echo 'PR HEAD changed during CI' >&2; exit 1; }
git fetch origin main
git merge-base --is-ancestor origin/main HEAD || { echo 'Base changed during CI; update and rerun' >&2; exit 1; }
# Same squash body GitHub builds by default, minus Claude attribution lines.
body=$(gh pr view "$pr" --json commits --jq '[.commits[] | "* " + .messageHeadline + "\n\n" + ([.messageBody | splits("\n") | select(test("^(co-authored-by: claude|🤖 generated with \\[claude code\\])"; "i") | not)] | join("\n"))] | join("\n\n")')
gh pr merge "$pr" --squash --match-head-commit "$head_sha" --body "$body"
# Remote branches are deleted by delete_branch_on_merge; detach instead of switching to a main another worktree may hold.
branch=$(git branch --show-current)
if [[ -n "$branch" ]]; then
  git switch --quiet --detach
  git branch -D "$branch" || echo "Merged, but could not delete local branch $branch" >&2
fi
