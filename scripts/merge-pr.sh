#!/usr/bin/env bash
set -euo pipefail
pr=${1:?Usage: scripts/merge-pr.sh PR_NUMBER}
[[ "$pr" =~ ^[0-9]+$ ]] || { echo 'PR number must be numeric' >&2; exit 1; }
cd "$(git rev-parse --show-toplevel)"
[[ -z "$(git status --porcelain)" ]] || { echo 'Merge requires a clean checkout' >&2; exit 1; }
snapshot=$(gh pr view "$pr" --json headRefOid,state,baseRefName)
head_sha=$(python3 -c 'import json,sys; p=json.load(sys.stdin); assert p["state"] == "OPEN" and p["baseRefName"] == "main", "PR must be open and target main"; print(p["headRefOid"])' <<< "$snapshot")
[[ "$(git rev-parse HEAD)" == "$head_sha" ]] || { echo 'Checkout must match the latest PR HEAD' >&2; exit 1; }
git fetch origin main
git merge-base --is-ancestor origin/main HEAD || { echo 'Merge latest origin/main into this PR first' >&2; exit 1; }
workflow_state=$(gh api repos/{owner}/{repo}/actions/workflows/ci.yml --jq .state)
[[ "$workflow_state" == disabled_* ]] || { echo 'Local-only policy requires GitHub CI to be disabled' >&2; exit 1; }
log=$(mktemp -t reggit-local-ci.XXXXXX)
echo "Local CI log: $log"
python3 scripts/local-ci.py 2>&1 | tee "$log"
[[ "$(git rev-parse HEAD)" == "$head_sha" && -z "$(git status --porcelain)" ]] || { echo 'Checkout changed after validation' >&2; exit 1; }
git fetch origin main
git merge-base --is-ancestor origin/main HEAD || { echo 'Base changed during CI; update and rerun' >&2; exit 1; }
gh pr merge "$pr" --squash --delete-branch --match-head-commit "$head_sha"
