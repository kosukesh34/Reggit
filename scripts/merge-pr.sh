#!/usr/bin/env bash
set -euo pipefail
pr=${1:?Usage: scripts/merge-pr.sh PR_NUMBER}
if [[ ! "$pr" =~ ^[0-9]+$ ]]; then
  echo 'PR number must be numeric' >&2
  exit 1
fi
snapshot=$(mktemp)
trap 'rm -f "$snapshot"' EXIT
gh pr view "$pr" --json headRefOid,state,statusCheckRollup > "$snapshot"
head_sha=$(python3 - "$snapshot" <<'PY'
import json, sys
p = json.load(open(sys.argv[1]))
checks = p.get('statusCheckRollup') or []
def successful(c):
    return (c.get('status') == 'COMPLETED' and c.get('conclusion') == 'SUCCESS') or c.get('state') == 'SUCCESS'
def harmless(c):
    return successful(c) or (c.get('status') == 'COMPLETED' and c.get('conclusion') in ('SKIPPED', 'NEUTRAL'))
required = {'test', 'lint', 'main'}
passed = {c.get('name', c.get('context')) for c in checks if successful(c)}
if p['state'] != 'OPEN' or not required <= passed or not all(harmless(c) for c in checks):
    sys.exit('Merge refused: latest HEAD must pass test, lint, main and have no failing/pending checks.')
print(p['headRefOid'])
PY
)
gh pr merge "$pr" --squash --delete-branch --match-head-commit "$head_sha"
